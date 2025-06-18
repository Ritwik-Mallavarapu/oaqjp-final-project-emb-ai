import os
import fitz # PyMuPDF
import numpy as np
import faiss
import json
from sentence_transformers import SentenceTransformer
from flask import current_app
from llama_cpp import Llama # Import Llama

# Paths (ensure these are correctly pointing to your instance folder from app/services/)
INSTANCE_PATH_FOR_AI = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'instance'))
FAISS_DATA_PATH_FOR_AI = os.path.join(INSTANCE_PATH_FOR_AI, 'faiss_data')
AI_MODELS_PATH_FOR_AI = os.path.join(INSTANCE_PATH_FOR_AI, 'ai_models') # For Llama model
os.makedirs(FAISS_DATA_PATH_FOR_AI, exist_ok=True)
os.makedirs(AI_MODELS_PATH_FOR_AI, exist_ok=True) # Ensure AI models path also exists

# Define a default model name or path (user should replace 'your_llama_model.gguf')
# This should ideally come from config.
DEFAULT_LLAMA_MODEL_FILENAME = "llama-2-7b-chat.Q4_K_M.gguf" # Using shell variable here

class AIService:
    def __init__(self, st_model_name='all-MiniLM-L6-v2', llama_model_filename=None):
        if current_app:
            current_app.logger.info(f"Initializing AIService with ST model: {st_model_name}")
        else:
            print(f"Initializing AIService (no app context) with ST model: {st_model_name}")

        self.st_model = SentenceTransformer(st_model_name)
        self.dimension = self.st_model.get_sentence_embedding_dimension()

        self.faiss_index_file = os.path.join(FAISS_DATA_PATH_FOR_AI, 'faiss_index.idx')
        self.text_chunks_map_file = os.path.join(FAISS_DATA_PATH_FOR_AI, 'text_chunks_map.json')

        self.faiss_index = None
        self.text_chunks_map = {}
        self._load_index_and_map()

        # Initialize Llama
        self.llm = None
        effective_llama_model_filename = llama_model_filename if llama_model_filename is not None else DEFAULT_LLAMA_MODEL_FILENAME

        self.llama_model_path = os.path.join(AI_MODELS_PATH_FOR_AI, effective_llama_model_filename)

        self._log(f"Attempting to load Llama model from: {self.llama_model_path}")
        try:
            if os.path.exists(self.llama_model_path):
                self.llm = Llama(model_path=self.llama_model_path, n_ctx=2048, verbose=False)
                self._log("Llama model loaded successfully.")
            else:
                self._log(f"Llama model file not found at {self.llama_model_path}. LLM functionality will be disabled.", level="error")
        except Exception as e:
            self._log(f"Error loading Llama model: {e}. LLM functionality will be disabled.", level="error")
            self.llm = None


    def _log(self, message, level="info"):
        if current_app:
            if level == "error": current_app.logger.error(message)
            elif level == "warning": current_app.logger.warning(message)
            else: current_app.logger.info(message)
        else: print(f"AIService LOG ({level.upper()}): {message}")

    def _load_index_and_map(self):
        try:
            if os.path.exists(self.faiss_index_file) and os.path.exists(self.text_chunks_map_file):
                self.faiss_index = faiss.read_index(self.faiss_index_file)
                with open(self.text_chunks_map_file, 'r', encoding='utf-8') as f:
                    self.text_chunks_map = {int(k): v for k, v in json.load(f).items()}
                self._log(f"FAISS index and text map loaded. Index size: {self.faiss_index.ntotal if self.faiss_index else 0}")
            else:
                self._log("No existing FAISS index found. A new one will be created.")
                self.faiss_index = faiss.IndexFlatL2(self.dimension)
        except Exception as e:
            self._log(f"Error loading FAISS index or map: {e}. Reinitializing.", level="error")
            self.faiss_index = faiss.IndexFlatL2(self.dimension)
            self.text_chunks_map = {}

    def _save_index_and_map(self):
        if self.faiss_index is not None:
            faiss.write_index(self.faiss_index, self.faiss_index_file)
            with open(self.text_chunks_map_file, 'w', encoding='utf-8') as f:
                json.dump(self.text_chunks_map, f, indent=4)
            self._log(f"FAISS index and text map saved. Index size: {self.faiss_index.ntotal}")
        else:
            self._log("Attempted to save FAISS index, but index is None.", level="warning")

    def _extract_text_from_pdf(self, pdf_path):
        text = ""
        try:
            doc = fitz.open(pdf_path)
            for page_num in range(len(doc)):
                page = doc.load_page(page_num)
                text += page.get_text("text") + "\n\n"
            doc.close()
        except Exception as e:
            self._log(f"Error extracting text from PDF {pdf_path}: {e}", level="error")
            return None
        return text

    def _chunk_text(self, text, chunk_size=500, overlap=50):
        chunks = []
        start = 0
        text_len = len(text)
        while start < text_len:
            end = min(start + chunk_size, text_len)
            chunks.append(text[start:end])
            if end == text_len: break
            start += chunk_size - overlap
        return chunks

    def process_and_add_pdf(self, pdf_path, pdf_filename="Unknown PDF"):
        self._log(f"Processing PDF: {pdf_filename} from path: {pdf_path}")
        text = self._extract_text_from_pdf(pdf_path)
        if not text: self._log(f"Could not extract text from {pdf_filename}.", level="warning"); return False
        chunks = self._chunk_text(text)
        if not chunks: self._log(f"No text chunks generated for {pdf_filename}.", level="warning"); return False
        self._log(f"Generated {len(chunks)} chunks for {pdf_filename}.")
        if self.faiss_index is None or self.faiss_index.d != self.dimension:
             self._log("Re-initializing FAISS index.", level="warning")
             self.faiss_index = faiss.IndexFlatL2(self.dimension); self.text_chunks_map = {}
        embeddings = self.st_model.encode(chunks, convert_to_tensor=False, show_progress_bar=False)
        if embeddings.ndim == 1: embeddings = np.array([embeddings], dtype=np.float32)
        elif embeddings.ndim == 2: embeddings = np.array(embeddings, dtype=np.float32)
        else: self._log(f"Unexpected embedding dim: {embeddings.ndim}", level="error"); return False
        for i, chunk_text in enumerate(chunks):
            current_index_pos = self.faiss_index.ntotal
            self.faiss_index.add(np.array([embeddings[i]]))
            self.text_chunks_map[current_index_pos] = {'text': chunk_text, 'source': pdf_filename}
        self._save_index_and_map()
        self._log(f"Successfully processed {pdf_filename} for FAISS index.")
        return True

    def search_rag_context(self, query_text, k=3):
        if self.faiss_index is None or self.faiss_index.ntotal == 0:
            self._log("FAISS index empty for search.", level="warning"); return []
        query_embedding = self.st_model.encode([query_text])
        distances, indices = self.faiss_index.search(np.array(query_embedding, dtype=np.float32), k)
        results = []
        if len(indices) > 0:
            for i in range(len(indices[0])):
                idx = indices[0][i]
                if idx != -1 and idx in self.text_chunks_map:
                    results.append({'text_chunk': self.text_chunks_map[idx]['text'],
                                    'source': self.text_chunks_map[idx]['source'],
                                    'distance': float(distances[0][i])})
        return results

    def generate_llm_response(self, prompt, max_tokens=250):
        if self.llm is None:
            self._log("Llama model not loaded.", level="error")
            return "AI model (Llama) is not available. Please check server configuration and model file."
        self._log(f"LLM prompt (first 100): {prompt[:100]}...")
        try:
            response = self.llm(prompt, max_tokens=max_tokens, stop=["User:", "###", "\n\n"], echo=False)
            generated_text = response['choices'][0]['text'].strip() if response and response['choices'] else ""
            self._log("LLM response generated.")
            return generated_text
        except Exception as e:
            self._log(f"Llama generation error: {e}", level="error")
            return "Error generating response from Llama model."

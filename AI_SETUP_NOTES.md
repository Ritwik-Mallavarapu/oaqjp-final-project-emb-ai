## AI Model Setup (Local Llama with RAG)

This project uses a local Llama model for its AI chatbot functionality, combined with a Retrieval Augmented Generation (RAG) system using FAISS.

### Dependencies:
Ensure all packages from \`requirements.txt\` are installed, including:
- \`sentence-transformers\`
- \`faiss-cpu\`
- \`PyMuPDF\`
- \`llama-cpp-python\`

### Llama Model Weights:
1.  **Download Llama Model**: You need to download a Llama model in GGUF format compatible with \`llama-cpp-python\`.
    A common source is Hugging Face (e.g., search for models by TheBloke). Choose a model size appropriate for your local hardware (CPU capabilities and RAM).
    The application currently defaults to looking for a model named: \`llama-2-7b-chat.Q4_K_M.gguf\`
2.  **Placement**: Place the downloaded GGUF model file into the \`instance/ai_models/\` directory.
    For example: \`instance/ai_models/llama-2-7b-chat.Q4_K_M.gguf\`
    If you use a different filename, you will need to adjust the \`DEFAULT_LLAMA_MODEL_FILENAME\` variable at the top of \`app/services/ai_service.py\` or modify how the \`AIService\` is initialized with the model path.
3.  **Configuration**: The \`AIService\` class attempts to load this model. Key parameters like context size (\`n_ctx\`) might need tuning based on your model and resources.

### FAISS Index:
- The FAISS index for the RAG system will be built from uploaded PDF documents and stored in the \`instance/faiss_data/\` directory (e.g., as \`faiss_index.idx\` and a corresponding mapping file).
- This index will be created/updated when new training materials are uploaded via the owner dashboard.

### Sentence Transformer Model:
- The \`sentence-transformers\` library will automatically download the required sentence embedding model (e.g., 'all-MiniLM-L6-v2') on its first use and cache it locally. Ensure your machine has internet access for this initial download.

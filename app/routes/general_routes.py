from flask import Blueprint, render_template, abort, request, flash, redirect, url_for, jsonify, current_app
from flask_login import current_user, login_required
from flask_mail import Message
from app import db, mail
from app.models.models import ServiceManual, User, Quiz, QuizAttempt, Feedback
from app.forms import QuizForm, FeedbackForm
from app.services.ai_service import AIService # Import AIService
from sqlalchemy.orm import selectinload # For eager loading option

general_bp = Blueprint('general_routes', __name__)

# --- Standard Routes (Home, Dashboard, Manual Viewing, Quiz Submission) ---
# These routes remain the same as the last correct version.
# For brevity, I'll only show the modified chatbot_query and necessary surrounding code.
# Assume home(), dashboard(), view_manual(), submit_quiz() are here and correct.

@general_bp.route("/")
@general_bp.route("/home")
def home():
    return render_template('home.html', title='Home')

@general_bp.route("/dashboard", methods=['GET', 'POST'])
@login_required
def dashboard():
    if current_user.role == 'owner':
        return redirect(url_for('owner_routes.dashboard'))

    feedback_form = FeedbackForm()
    if request.method == 'POST' and 'feedback_submit' in request.form:
        if feedback_form.validate_on_submit():
            feedback_entry = Feedback(user_id=current_user.id, feedback_text=feedback_form.feedback_text.data)
            db.session.add(feedback_entry)
            db.session.commit()
            flash('Your feedback has been submitted. Thank you!', 'success')
            return redirect(url_for('general_routes.dashboard'))

    manuals = ServiceManual.query.order_by(ServiceManual.upload_timestamp.desc()).all()
    user_attempts = {attempt.quiz_id: attempt for attempt in current_user.quiz_attempts}
    return render_template('employee_dashboard.html',
                           title='Employee Dashboard',
                           manuals=manuals,
                           user_attempts=user_attempts,
                           feedback_form=feedback_form)

@general_bp.route("/manual/<int:manual_id>", methods=['GET'])
@login_required
def view_manual(manual_id):
    manual = ServiceManual.query.get_or_404(manual_id)
    quiz = Quiz.query.filter_by(manual_id=manual.id).first()
    form = QuizForm()
    existing_attempt = None
    if quiz:
        existing_attempt = QuizAttempt.query.filter_by(user_id=current_user.id, quiz_id=quiz.id).first()
    return render_template('view_manual.html',
                           title=f"{manual.brand} {manual.model_name}",
                           manual=manual,
                           quiz=quiz,
                           form=form,
                           existing_attempt=existing_attempt)

@general_bp.route("/submit_quiz/<int:quiz_id>", methods=['POST'])
@login_required
def submit_quiz(quiz_id):
    quiz = Quiz.query.get_or_404(quiz_id)
    manual = ServiceManual.query.get_or_404(quiz.manual_id)
    score = 0
    q1_user_answer = request.form.get(f'q{quiz.id}_question1')
    if q1_user_answer == quiz.q1_correct_option: score += 1
    q2_user_answer = request.form.get(f'q{quiz.id}_question2')
    if q2_user_answer == quiz.q2_correct_option: score += 1
    q3_user_answer = request.form.get(f'q{quiz.id}_question3')
    if q3_user_answer == quiz.q3_correct_option: score += 1
    pass_status = score >= 2
    attempt = QuizAttempt.query.filter_by(user_id=current_user.id, quiz_id=quiz.id).first()
    if attempt:
        attempt.score = score; attempt.pass_status = pass_status; attempt.completed_timestamp = db.func.now()
    else:
        attempt = QuizAttempt(user_id=current_user.id, quiz_id=quiz.id, score=score, pass_status=pass_status)
        db.session.add(attempt)
    db.session.commit()
    try:
        subject = f"Quiz Attempt Summary for {manual.brand} {manual.model_name}"
        html_body = f"<p>Hello {current_user.username},</p><p>You completed a quiz for {manual.brand} {manual.model_name}.</p><p>Score: {score}/3. Status: {'Passed' if pass_status else 'Failed'}.</p>"
        msg = Message(subject, recipients=[current_user.email], html=html_body)
        mail.send(msg)
    except Exception as e:
        current_app.logger.error(f"Failed to send quiz email to {current_user.email}: {e}")
    flash(f'Quiz submitted! Score: {score}/3. {"Passed!" if pass_status else "Failed."}', 'success' if pass_status else 'warning')
    return redirect(url_for('general_routes.view_manual', manual_id=quiz.manual_id))


# --- Updated Chatbot Query Route with RAG and Local Llama ---
@general_bp.route("/chatbot_query", methods=['GET']) # Or POST if query is long
@login_required
def chatbot_query():
    query_text = request.args.get('query', '').lower().strip()
    if not query_text:
        return jsonify({'error': 'No query provided.'}), 400

    try:
        # It's better to manage AIService instance via app context or a factory,
        # but direct instantiation works if current_app is available for its logger.
        # Ensure AIService's __init__ can handle being called multiple times or is a singleton.
        # For this case, assume it's okay to instantiate per request for simplicity,
        # though Llama model loading on each call would be inefficient.
        # A better pattern: initialize AIService once in create_app and attach to 'app' or 'g'.
        # For this subtask, we'll proceed with direct instantiation, assuming Llama loads only once internally or is fast.
        ai_service = AIService()
    except Exception as e:
        current_app.logger.error(f"Failed to initialize AIService: {e}")
        return jsonify({'error': 'AI service is currently unavailable.'}), 500

    if ai_service.llm is None: # Check if Llama model loaded successfully within AIService
        return jsonify({'error': 'The AI model (Llama) is not available. Please check server configuration.'}), 503

    if ai_service.faiss_index is None or ai_service.faiss_index.ntotal == 0:
        # If no PDFs processed yet, try a direct LLM call without RAG context
        current_app.logger.warning("Chatbot query received, but FAISS index is empty. Attempting direct LLM call.")
        # Simple prompt for direct LLM call (less effective for specific manual queries)
        # This part can be improved with a more generic "assistant" prompt if no context.
        # For now, let's indicate that context is missing.
        # llm_response_text = ai_service.generate_llm_response(f"User question: {query_text}\nAnswer based on general knowledge if no specific context is provided about laptop repair manuals.")
        # return jsonify({'response': llm_response_text})
        # OR, more realistically for this app:
        return jsonify({'response': "I don't have any training materials (PDFs) in my knowledge base yet to answer questions about specific repair procedures. Please ask an owner to upload some manuals."})


    # 1. Get RAG context
    retrieved_contexts = ai_service.search_rag_context(query_text, k=3) # Get top 3 chunks

    if not retrieved_contexts:
        # No relevant context found, could still try a direct LLM call or a specific message
        current_app.logger.info(f"No relevant RAG context found for query: '{query_text}'.")
        # For now, let's respond that no specific context was found.
        # A direct call to LLM could be:
        # direct_prompt = f"The user asked: '{query_text}'. Since no specific documents were found, answer generally if possible or state that specific information is unavailable."
        # llm_response_text = ai_service.generate_llm_response(direct_prompt)
        # return jsonify({'response': llm_response_text})
        return jsonify({'response': "I couldn't find highly relevant information in the uploaded manuals for your query. You could try rephrasing or being more specific."})

    # 2. Construct Prompt for Llama
    context_str = "\n\n".join([chunk['text_chunk'] for chunk in retrieved_contexts])

    # Basic prompt template - this can be significantly improved!
    prompt = f"""You are an AI assistant for laptop repair technicians. Use the following context from service manuals to answer the user's question. If the context doesn't directly answer, say you couldn't find the specific information in the provided documents. Do not make up information outside of the context.

Context from manuals:
---
{context_str}
---

User's Question: {query_text}

Answer:"""

    current_app.logger.info(f"Constructed prompt for Llama (showing first 200 chars): {prompt[:200]}")

    # 3. Get Response from Llama
    llm_response_text = ai_service.generate_llm_response(prompt)

    # Basic formatting: replace newlines for HTML display if needed, or keep as is for pre-formatted text
    # llm_response_text = llm_response_text.replace("\n", "<br>")

    return jsonify({'response': llm_response_text})

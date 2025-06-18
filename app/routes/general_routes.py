from flask import Blueprint, render_template, abort, request, flash, redirect, url_for
from flask_login import current_user, login_required
from app import db
from app.models.models import ServiceManual, User, Quiz, QuizAttempt, Feedback # Ensure Feedback is imported
from app.forms import QuizForm, FeedbackForm # Ensure FeedbackForm is imported

general_bp = Blueprint('general_routes', __name__)

@general_bp.route("/")
@general_bp.route("/home")
def home():
    return render_template('home.html', title='Home')

@general_bp.route("/dashboard", methods=['GET', 'POST']) # Allow POST for employee feedback form
@login_required
def dashboard():
    if current_user.role == 'owner':
        return redirect(url_for('owner_routes.dashboard')) # owner_bp has /owner prefix

    # Employee specific logic (including feedback form handling)
    feedback_form = FeedbackForm()
    if request.method == 'POST' and 'feedback_submit' in request.form: # Check if feedback form was submitted
        if feedback_form.validate_on_submit():
            feedback_entry = Feedback(user_id=current_user.id, feedback_text=feedback_form.feedback_text.data)
            db.session.add(feedback_entry)
            db.session.commit()
            flash('Your feedback has been submitted. Thank you!', 'success')
            return redirect(url_for('general_routes.dashboard')) # Redirect to GET to clear form
        else:
            # Handle form validation errors if any for feedback form
            flash('There was an error with your feedback submission.', 'danger')

    # For GET request or if feedback form was not the one submitted / had errors
    manuals = ServiceManual.query.order_by(ServiceManual.upload_timestamp.desc()).all()
    user_attempts = {attempt.quiz_id: attempt for attempt in current_user.quiz_attempts}
    return render_template('employee_dashboard.html',
                           title='Employee Dashboard',
                           manuals=manuals,
                           user_attempts=user_attempts,
                           feedback_form=feedback_form)

@general_bp.route("/manual/<int:manual_id>", methods=['GET']) # Quiz submission is a separate route
@login_required
def view_manual(manual_id):
    manual = ServiceManual.query.get_or_404(manual_id)
    quiz = Quiz.query.filter_by(manual_id=manual.id).first()
    form = QuizForm() # For CSRF token and submit button
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
    score = 0
    # Simplified scoring logic from previous step
    q1_user_answer = request.form.get(f'q{quiz.id}_question1')
    if q1_user_answer == quiz.q1_correct_option: score += 1
    q2_user_answer = request.form.get(f'q{quiz.id}_question2')
    if q2_user_answer == quiz.q2_correct_option: score += 1
    q3_user_answer = request.form.get(f'q{quiz.id}_question3')
    if q3_user_answer == quiz.q3_correct_option: score += 1

    pass_status = score >= 2

    attempt = QuizAttempt.query.filter_by(user_id=current_user.id, quiz_id=quiz.id).first()
    if attempt:
        attempt.score = score
        attempt.pass_status = pass_status
        attempt.completed_timestamp = db.func.now()
    else:
        attempt = QuizAttempt(user_id=current_user.id, quiz_id=quiz.id, score=score, pass_status=pass_status)
        db.session.add(attempt)

    db.session.commit()
    flash(f'Quiz submitted! Your score: {score}/{3}. {"Passed!" if pass_status else "Failed."}', 'success' if pass_status else 'warning')
    return redirect(url_for('general_routes.view_manual', manual_id=quiz.manual_id))

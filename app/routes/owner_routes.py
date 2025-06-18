import os
from werkzeug.utils import secure_filename
from flask import Blueprint, render_template, abort, redirect, url_for, flash, request, current_app
from flask_login import current_user, login_required
from functools import wraps
from app import db
from app.models.models import ServiceManual, Feedback, User, Quiz
from app.forms import AddManualForm, EditManualForm, QuizManagementForm, AITrainingMaterialForm
from sqlalchemy.orm import selectinload
from app.services.ai_service import AIService # Import AIService

owner_bp = Blueprint('owner_routes', __name__, url_prefix='/owner')

# (owner_required decorator and other routes remain the same as previous correct version)
def owner_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or current_user.role != 'owner':
            flash('You do not have permission to access this page.', 'danger')
            return redirect(url_for('general_routes.home'))
        return f(*args, **kwargs)
    return decorated_function

@owner_bp.route('/dashboard')
@login_required
@owner_required
def dashboard():
    manuals_count = ServiceManual.query.count()
    feedback_count = Feedback.query.count()
    return render_template('owner/owner_dashboard.html', title='Owner Dashboard', manuals_count=manuals_count, feedback_count=feedback_count)

@owner_bp.route('/manuals')
@login_required
@owner_required
def list_manuals():
    manuals = ServiceManual.query.order_by(ServiceManual.brand, ServiceManual.model_name).all()
    return render_template('owner/list_manuals.html', title='Manage Service Manuals', manuals=manuals)

@owner_bp.route('/manual/add', methods=['GET', 'POST'])
@login_required
@owner_required
def add_manual():
    form = AddManualForm()
    if form.validate_on_submit():
        manual = ServiceManual(brand=form.brand.data,
                               model_name=form.model_name.data,
                               content=form.content.data,
                               uploaded_by_id=current_user.id)
        db.session.add(manual)
        db.session.commit()
        flash(f'Service manual for {manual.brand} {manual.model_name} added. Now add a quiz.', 'success')
        return redirect(url_for('owner_routes.manage_quiz', manual_id=manual.id))
    return render_template('owner/manage_manual.html', title='Add New Service Manual', form=form, legend='New Service Manual')

@owner_bp.route('/manual/<int:manual_id>/edit', methods=['GET', 'POST'])
@login_required
@owner_required
def edit_manual(manual_id):
    manual = ServiceManual.query.get_or_404(manual_id)
    form = EditManualForm(obj=manual)
    if form.validate_on_submit():
        manual.brand = form.brand.data
        manual.model_name = form.model_name.data
        manual.content = form.content.data
        db.session.commit()
        flash(f'Service manual for {manual.brand} {manual.model_name} updated.', 'success')
        return redirect(url_for('owner_routes.list_manuals'))
    return render_template('owner/manage_manual.html', title='Edit Service Manual', form=form, legend=f'Edit: {manual.brand} {manual.model_name}', manual=manual)

@owner_bp.route('/manual/<int:manual_id>/delete', methods=['POST'])
@login_required
@owner_required
def delete_manual(manual_id):
    manual = ServiceManual.query.get_or_404(manual_id)
    db.session.delete(manual)
    db.session.commit()
    flash(f'Service manual for {manual.brand} {manual.model_name} and its quizzes deleted.', 'success')
    return redirect(url_for('owner_routes.list_manuals'))

@owner_bp.route('/manual/<int:manual_id>/quiz', methods=['GET', 'POST'])
@login_required
@owner_required
def manage_quiz(manual_id):
    manual = ServiceManual.query.get_or_404(manual_id)
    quiz = Quiz.query.filter_by(manual_id=manual.id).first()
    form = QuizManagementForm(obj=quiz)
    if form.validate_on_submit():
        if quiz is None:
            quiz = Quiz(manual_id=manual.id)
            db.session.add(quiz)
            flash_message = 'Quiz created successfully!'
        else:
            flash_message = 'Quiz updated successfully!'
        quiz.question1 = form.question1.data; quiz.q1_option_a = form.q1_option_a.data; quiz.q1_option_b = form.q1_option_b.data; quiz.q1_option_c = form.q1_option_c.data; quiz.q1_correct_option = form.q1_correct_option.data;
        quiz.question2 = form.question2.data; quiz.q2_option_a = form.q2_option_a.data; quiz.q2_option_b = form.q2_option_b.data; quiz.q2_option_c = form.q2_option_c.data; quiz.q2_correct_option = form.q2_correct_option.data;
        quiz.question3 = form.question3.data; quiz.q3_option_a = form.q3_option_a.data; quiz.q3_option_b = form.q3_option_b.data; quiz.q3_option_c = form.q3_option_c.data; quiz.q3_correct_option = form.q3_correct_option.data;
        db.session.commit()
        flash(flash_message, 'success')
        return redirect(url_for('owner_routes.list_manuals'))
    legend = 'Edit Quiz' if quiz else 'Add New Quiz'
    return render_template('owner/manage_quiz.html', title=f'{legend} for {manual.brand} {manual.model_name}', form=form, legend=legend, manual=manual, quiz=quiz)

@owner_bp.route('/feedback')
@login_required
@owner_required
def view_all_feedback():
    feedbacks = Feedback.query.order_by(Feedback.submitted_timestamp.desc()).all()
    return render_template('owner/view_feedback.html', title="View Feedback", feedbacks=feedbacks)

@owner_bp.route('/ai-training-upload', methods=['GET', 'POST'])
@login_required
@owner_required
def ai_training_upload():
    form = AITrainingMaterialForm()
    if form.validate_on_submit():
        pdf = form.pdf_file.data
        filename = secure_filename(pdf.filename)
        upload_folder = current_app.config.get('UPLOAD_FOLDER_AI_TRAINING', os.path.join(current_app.instance_path, 'training_materials'))
        # os.makedirs(upload_folder, exist_ok=True) # Already handled in __init__.py
        file_path = os.path.join(upload_folder, filename)
        pdf.save(file_path)

        flash(f'PDF "{filename}" uploaded successfully. Processing for AI knowledge base...', 'info')
        try:
            # Make sure current_app is available here or AIService is initialized with app context
            # If AIService() uses current_app in its __init__ or methods, it needs app context.
            # This direct instantiation should work if current_app is available (typical in Flask routes).
            ai_service = AIService()
            if ai_service.process_and_add_pdf(file_path, filename):
                flash(f'PDF "{filename}" processed and added to AI knowledge base.', 'success')
            else:
                flash(f'PDF "{filename}" uploaded, but there was an issue adding it to the AI knowledge base.', 'warning')
        except Exception as e:
            current_app.logger.error(f"Error during AI processing for {filename}: {e}")
            flash(f'PDF "{filename}" uploaded, but AI processing failed: {str(e)}', 'danger')

        return redirect(url_for('owner_routes.ai_training_upload'))

    return render_template('owner/ai_training_upload.html', title='Upload AI Training Material', form=form)

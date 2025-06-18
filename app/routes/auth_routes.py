from flask import Blueprint, render_template, url_for, flash, redirect, request
from app import db, login_manager # Assuming login_manager is also in app package __init__
from app.forms import RegistrationForm, LoginForm
from app.models.models import User
from flask_login import login_user, current_user, logout_user, login_required
from werkzeug.security import generate_password_hash # Import directly

auth_bp = Blueprint('auth_routes', __name__)

# @login_manager.user_loader # This should be in __init__.py or models.py where User is defined
# def load_user(user_id):
#    return User.query.get(int(user_id))
# The user_loader is already in models.py, so it's commented out here.

@auth_bp.route("/register", methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('general_routes.home'))
    form = RegistrationForm()
    if form.validate_on_submit():
        # Use the set_password method from the User model
        # Create user instance first, then set password
        user = User(username=form.username.data, email=form.email.data)
        user.set_password(form.password.data) # This sets user.password_hash
        db.session.add(user)
        db.session.commit()
        flash('Your account has been created! You are now able to log in', 'success')
        return redirect(url_for('auth_routes.login'))
    return render_template('register.html', title='Register', form=form)

@auth_bp.route("/login", methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('general_routes.home'))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data).first()
        if user and user.check_password(form.password.data):
            login_user(user, remember=form.remember.data)
            next_page = request.args.get('next')
            flash('Login successful!', 'success')
            # Make sure general_routes.home exists before redirecting
            # For now, redirect to a placeholder if it doesn't, or ensure it's created.
            # It is created in this script.
            return redirect(next_page) if next_page else redirect(url_for('general_routes.home'))
        else:
            flash('Login Unsuccessful. Please check email and password', 'danger')
    return render_template('login.html', title='Login', form=form)

@auth_bp.route("/logout")
@login_required
def logout():
    logout_user()
    flash('You have been logged out.', 'info')
    return redirect(url_for('general_routes.home'))

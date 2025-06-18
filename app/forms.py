from flask_wtf import FlaskForm
from wtforms import StringField, PasswordField, SubmitField, BooleanField, TextAreaField, RadioField # Added RadioField
from wtforms.validators import DataRequired, Length, Email, EqualTo, ValidationError
from app.models.models import User

class RegistrationForm(FlaskForm):
    username = StringField('Username',
                           validators=[DataRequired(), Length(min=2, max=80)])
    email = StringField('Email',
                        validators=[DataRequired(), Email()])
    password = PasswordField('Password', validators=[DataRequired(), Length(min=6)])
    confirm_password = PasswordField('Confirm Password',
                                     validators=[DataRequired(), EqualTo('password')])
    submit = SubmitField('Sign Up')

    def validate_username(self, username):
        user = User.query.filter_by(username=username.data).first()
        if user:
            raise ValidationError('That username is taken. Please choose a different one.')

    def validate_email(self, email):
        user = User.query.filter_by(email=email.data).first()
        if user:
            raise ValidationError('That email is taken. Please choose a different one.')

class LoginForm(FlaskForm):
    email = StringField('Email',
                        validators=[DataRequired(), Email()])
    password = PasswordField('Password', validators=[DataRequired()])
    remember = BooleanField('Remember Me')
    submit = SubmitField('Login')

class QuizForm(FlaskForm):
    submit = SubmitField('Submit Answers')

class FeedbackForm(FlaskForm):
    feedback_text = TextAreaField('Your Feedback', validators=[DataRequired(), Length(min=10, max=1000)])
    submit = SubmitField('Submit Feedback')

class AddManualForm(FlaskForm):
    brand = StringField('Laptop Brand', validators=[DataRequired(), Length(max=100)])
    model_name = StringField('Laptop Model Name', validators=[DataRequired(), Length(max=100)])
    content = TextAreaField('Service Manual Content (Text or Markdown)', validators=[DataRequired()])
    submit = SubmitField('Save Manual')

class EditManualForm(AddManualForm): # Inherits fields and validators
    pass # No changes needed if fields are the same for editing

class QuizManagementForm(FlaskForm):
    # Question 1
    question1 = TextAreaField('Question 1', validators=[DataRequired(), Length(max=500)])
    q1_option_a = StringField('Option A', validators=[DataRequired(), Length(max=200)])
    q1_option_b = StringField('Option B', validators=[DataRequired(), Length(max=200)])
    q1_option_c = StringField('Option C', validators=[DataRequired(), Length(max=200)])
    q1_correct_option = RadioField('Correct Answer for Q1', choices=[('a','A'), ('b','B'), ('c','C')], validators=[DataRequired()])

    # Question 2
    question2 = TextAreaField('Question 2', validators=[DataRequired(), Length(max=500)])
    q2_option_a = StringField('Option A', validators=[DataRequired(), Length(max=200)])
    q2_option_b = StringField('Option B', validators=[DataRequired(), Length(max=200)])
    q2_option_c = StringField('Option C', validators=[DataRequired(), Length(max=200)])
    q2_correct_option = RadioField('Correct Answer for Q2', choices=[('a','A'), ('b','B'), ('c','C')], validators=[DataRequired()])

    # Question 3
    question3 = TextAreaField('Question 3', validators=[DataRequired(), Length(max=500)])
    q3_option_a = StringField('Option A', validators=[DataRequired(), Length(max=200)])
    q3_option_b = StringField('Option B', validators=[DataRequired(), Length(max=200)])
    q3_option_c = StringField('Option C', validators=[DataRequired(), Length(max=200)])
    q3_correct_option = RadioField('Correct Answer for Q3', choices=[('a','A'), ('b','B'), ('c','C')], validators=[DataRequired()])

    submit = SubmitField('Save Quiz')

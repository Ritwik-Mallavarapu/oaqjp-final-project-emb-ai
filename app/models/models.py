from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from app import db, login_manager # Import db and login_manager from the app package
from datetime import datetime

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

class User(db.Model, UserMixin):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(256), nullable=False) # Increased length for stronger hashes
    role = db.Column(db.String(20), nullable=False, default='employee') # 'employee' or 'owner'

    feedback = db.relationship('Feedback', backref='author', lazy=True)
    quiz_attempts = db.relationship('QuizAttempt', backref='user', lazy=True)
    uploaded_manuals = db.relationship('ServiceManual', backref='uploader', lazy=True)

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    def __repr__(self):
        return f'<User {self.username}>'

class ServiceManual(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    brand = db.Column(db.String(100), nullable=False)
    model_name = db.Column(db.String(100), nullable=False)
    content = db.Column(db.Text, nullable=False) # Storing content as TEXT for now. Could be path to a file.
    uploaded_by_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    upload_timestamp = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    quizzes = db.relationship('Quiz', backref='manual', lazy=True, cascade="all, delete-orphan")

    def __repr__(self):
        return f'<ServiceManual {self.brand} {self.model_name}>'

class Quiz(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    manual_id = db.Column(db.Integer, db.ForeignKey('service_manual.id'), nullable=False)

    # Question 1
    question1 = db.Column(db.String(500), nullable=False)
    q1_option_a = db.Column(db.String(200), nullable=False)
    q1_option_b = db.Column(db.String(200), nullable=False)
    q1_option_c = db.Column(db.String(200), nullable=False)
    q1_correct_option = db.Column(db.String(1), nullable=False) # 'a', 'b', or 'c'

    # Question 2
    question2 = db.Column(db.String(500), nullable=False)
    q2_option_a = db.Column(db.String(200), nullable=False)
    q2_option_b = db.Column(db.String(200), nullable=False)
    q2_option_c = db.Column(db.String(200), nullable=False)
    q2_correct_option = db.Column(db.String(1), nullable=False)

    # Question 3
    question3 = db.Column(db.String(500), nullable=False)
    q3_option_a = db.Column(db.String(200), nullable=False)
    q3_option_b = db.Column(db.String(200), nullable=False)
    q3_option_c = db.Column(db.String(200), nullable=False)
    q3_correct_option = db.Column(db.String(1), nullable=False)

    attempts = db.relationship('QuizAttempt', backref='quiz', lazy=True, cascade="all, delete-orphan")

    def __repr__(self):
        return f'<Quiz {self.id} for Manual {self.manual_id}>'

class QuizAttempt(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    quiz_id = db.Column(db.Integer, db.ForeignKey('quiz.id'), nullable=False)
    score = db.Column(db.Integer, nullable=False) # e.g., 0, 1, 2, or 3 for a 3-question quiz
    pass_status = db.Column(db.Boolean, nullable=False, default=False)
    completed_timestamp = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    def __repr__(self):
        return f'<QuizAttempt by User {self.user_id} for Quiz {self.quiz_id}>'

class Feedback(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    feedback_text = db.Column(db.Text, nullable=False)
    submitted_timestamp = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)

    def __repr__(self):
        return f'<Feedback {self.id} by User {self.user_id}>'

from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
import click # For CLI commands
from flask.cli import with_appcontext
from datetime import timedelta # Added

# Initialize extensions
db = SQLAlchemy()
login_manager = LoginManager()

def create_app():
    app = Flask(__name__)
    app.config['SECRET_KEY'] = 'your_secret_key'  # Change this in production!
    app.config['WTF_CSRF_ENABLED'] = True # Added for CSRF protection
    app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///site.db' # Using SQLite for now
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False # Optional: suppress a warning

    # Configure session lifetime
    app.permanent_session_lifetime = timedelta(minutes=30) # Added

    # Initialize extensions with the app
    db.init_app(app)
    login_manager.init_app(app)

    login_manager.login_view = 'auth_routes.login'
    login_manager.login_message_category = 'info'

    # Import models here to ensure they are registered with SQLAlchemy instance
    with app.app_context():
        from .models import models # Adjusted import

    # Import and register Blueprints
    from .routes.auth_routes import auth_bp as auth_blueprint
    app.register_blueprint(auth_blueprint, url_prefix='/auth')

    from .routes.general_routes import general_bp as general_blueprint
    app.register_blueprint(general_blueprint)

    from .routes.owner_routes import owner_bp as owner_blueprint # Added
    app.register_blueprint(owner_blueprint) # Added (url_prefix is in owner_bp definition)


    @click.command(name='create_tables')
    @with_appcontext
    def create_tables_command():
        db.create_all()
        click.echo('Database tables created.')

    app.cli.add_command(create_tables_command)

    return app

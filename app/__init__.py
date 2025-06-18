import os # Ensure os is imported at the top
from flask import Flask, Blueprint
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from flask_mail import Mail # Assuming Mail is used

db = SQLAlchemy()
login_manager = LoginManager()
mail = Mail() # Assuming global mail instance

def create_app(config_class=None): # Simplified config loading for this example
    app = Flask(__name__, instance_relative_config=True) # instance_relative_config=True is good practice

    # Configuration
    app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'dev_secret_key_change_me_in_prod')
    app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'sqlite:///site.db')
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['WTF_CSRF_ENABLED'] = True # Enable CSRF Protection
    app.config['UPLOAD_FOLDER_AI_TRAINING'] = os.path.join(app.instance_path, 'training_materials')

    # Ensure instance folder exists
    try:
        os.makedirs(app.instance_path, exist_ok=True)
        os.makedirs(app.config['UPLOAD_FOLDER_AI_TRAINING'], exist_ok=True)
    except OSError:
        pass # Handle error if needed, e.g. log it

    # Flask-Mail configuration (example for console output)
    app.config['MAIL_SERVER'] = 'localhost'
    app.config['MAIL_PORT'] = 25
    app.config['MAIL_USE_TLS'] = False
    app.config['MAIL_USE_SSL'] = False
    app.config['MAIL_USERNAME'] = None
    app.config['MAIL_PASSWORD'] = None
    app.config['MAIL_DEFAULT_SENDER'] = ('Dad and Dude Co. Tech Support', 'noreply@dadanddude.com')
    app.config['MAIL_DEBUG'] = True # Output emails to console/log

    db.init_app(app)
    login_manager.init_app(app)
    mail.init_app(app)

    login_manager.login_view = 'auth_routes.login' # Adjust if your login route is elsewhere
    login_manager.login_message_category = 'info'

    from app.models.models import User # User loader needs to be defined
    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))

    # Import and register blueprints
    from app.routes.general_routes import general_bp
    from app.routes.auth_routes import auth_bp
    from app.routes.owner_routes import owner_bp

    app.register_blueprint(general_bp)
    app.register_blueprint(auth_bp, url_prefix='/auth') # Example prefix
    app.register_blueprint(owner_bp) # owner_bp already has /owner prefix

    # CLI commands e.g. create_tables
    from flask.cli import with_appcontext
    import click

    @click.command(name='create_tables')
    @with_appcontext
    def create_tables_command():
        db.create_all()
        click.echo('Database tables created.')
    app.cli.add_command(create_tables_command)

    return app

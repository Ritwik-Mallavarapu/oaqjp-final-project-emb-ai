import unittest
from app.models.models import User # Assuming User model can be imported if app context is handled or not needed for this test
from werkzeug.security import generate_password_hash

# Mocking db and app context for model testing without full app setup
# This is a simplified approach. For real testing, Flask-Testing or pytest-flask is better.
class MockApp:
    def __init__(self):
        self.extensions = {}

class MockSQLAlchemy:
    def __init__(self):
        self.Model = object # Base class for models
        self.Column = lambda *args, **kwargs: None
        self.Integer = None
        self.String = lambda *args, **kwargs: None
        self.Text = None
        self.DateTime = None
        self.ForeignKey = lambda *args, **kwargs: None
        self.relationship = lambda *args, **kwargs: None
        self.session = MockSession()

class MockSession:
    def add(self, instance): pass
    def commit(self): pass
    def delete(self, instance): pass
    def query(self, model): return MockQuery(model)

class MockQuery:
    def __init__(self, model): self.model = model
    def get(self, ident): return None
    def filter_by(self, **kwargs): return self
    def first(self): return None
    def all(self): return []
    def count(self): return 0


# If User model relies on db.Model, we need to patch it or use a test db.
# For simplicity, this test will focus purely on methods not directly using db session.
# If User.set_password or check_password use db, this test would need more setup.
# Our User model's password methods are self-contained.

class TestUserModel(unittest.TestCase):

    def test_password_setter(self):
        u = User()
        u.set_password('cat')
        self.assertIsNotNone(u.password_hash)
        self.assertNotEqual(u.password_hash, 'cat')

    def test_password_checker(self):
        u = User()
        u.set_password('cat')
        self.assertTrue(u.check_password('cat'))
        self.assertFalse(u.check_password('dog'))

    def test_password_hashes_are_random(self):
        u1 = User()
        u1.set_password('supersecret')
        u2 = User()
        u2.set_password('supersecret')
        self.assertNotEqual(u1.password_hash, u2.password_hash)

    def test_user_representation(self):
        u = User(username='john')
        self.assertEqual(repr(u), '<User john>')

if __name__ == '__main__':
    unittest.main()

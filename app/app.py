from app import create_app

app = create_app()

if __name__ == '__main__':
    # debug=True is fine for dev, but consider FLASK_DEBUG env var for `flask run`
    app.run(debug=True)

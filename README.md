# Dad and Dude Co. Laptop Repair Training Platform

This web application provides a training and assistance platform for laptop repair technicians at Dad and Dude Co. It features separate dashboards for employees and owners, with role-specific functionalities.

## Core Features

*   **User Authentication**: Secure registration and login for employees and owners.
*   **Employee Dashboard**:
    *   Access to service manuals.
    *   MCQ quizzes for each manual with progress tracking.
    *   Feedback submission system.
    *   AI Repair Assistant for procedural guidance.
*   **Owner Dashboard**:
    *   CRUD (Create, Read, Update, Delete) operations for service manuals.
    *   Management of quizzes associated with each manual.
    *   Viewing employee feedback.
    *   Uploading PDF documents to serve as training material for the AI.
*   **Email Notifications**: Quiz completion summaries sent to employees (currently logs to console).
*   **Custom Styling**: A unique theme for the application.

## AI Chatbot (Local Llama with RAG)

The platform includes an AI Repair Assistant that uses a locally run Llama language model combined with a Retrieval Augmented Generation (RAG) system.

*   **Knowledge Base**: Owners can upload PDF documents (e.g., service manuals, troubleshooting guides). These documents are processed, and their text content is indexed into a FAISS vector store.
*   **Answering Queries**: When an employee asks a question, the system retrieves the most relevant text chunks from the indexed PDFs and provides them as context to the Llama model. Llama then generates an answer based on this information.
*   **Local Processing**: Both the sentence embedding generation (for RAG) and the Llama model inference run locally on the server hosting the application.

**For detailed instructions on setting up the AI components, including downloading the Llama model, please refer to [AI_SETUP_NOTES.md](AI_SETUP_NOTES.md).**

## Getting Started (Development)

1.  **Prerequisites**: Python 3.8+, pip.
2.  **Clone the repository** (if applicable).
3.  **Create and activate a Python virtual environment**:
    \`\`\`bash
    python3 -m venv venv
    source venv/bin/activate  # On macOS/Linux
    # venv\Scripts\activate   # On Windows
    \`\`\`
4.  **Install dependencies**:
    \`\`\`bash
    pip install -r requirements.txt
    \`\`\`
5.  **Set up the AI Model**: Follow the instructions in [AI_SETUP_NOTES.md](AI_SETUP_NOTES.md) to download and place the Llama GGUF model.
6.  **Initialize the Database**:
    \`\`\`bash
    # Ensure FLASK_APP is set correctly, e.g.:
    # export FLASK_APP="app:create_app()" (macOS/Linux)
    # $env:FLASK_APP="app:create_app()" (Windows PowerShell)
    # Assuming app is the directory containing __init__.py with create_app()
    # or app.py contains the app instance. If FLASK_APP=app.app, use that.
    # The create_app in app/__init__.py suggests FLASK_APP=app should work.
    flask create_tables
    \`\`\`
7.  **Run the application**:
    \`\`\`bash
    flask run
    \`\`\`
    The application will typically be available at \`http://127.0.0.1:5000/\`.

8.  **Create an Owner Account**: After registering a user, you may need to manually set their role to 'owner' in the database or via \`flask shell\` to access owner functionalities. Example using \`flask shell\`:
    \`\`\`python
    from app import db
    from app.models.models import User
    # Find your user
    user = User.query.filter_by(email='your_email@example.com').first()
    if user:
        user.role = 'owner'
        db.session.commit()
        print(f"User {user.username} role set to owner.")
    else:
        print("User not found.")
    \`\`\`

# For older instructions that might have been in the README:
# Repository for final project

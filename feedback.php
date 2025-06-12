<?php
require_once 'php/config.php'; // Adjust path if necessary

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html"); // Adjust path if necessary
    exit;
}
$username = $_SESSION['username'];
$feedback_submitted_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']); // Clear the message after displaying
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Submit Feedback</h1>
    </header>
    <nav>
        <ul class="nav justify-content-center bg-dark">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="training.php">Training Modules</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="ai_assistance.php">AI Repair Assistance</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="php/logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <h2>We value your input, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>Please use the form below to report any issues, bugs, or suggestions for improvement.</p>

        <?php if ($feedback_submitted_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($feedback_submitted_message); ?>
        </div>
        <?php endif; ?>

        <form action="php/submit_feedback.php" method="post">
            <div class="mb-3">
                <label for="feedback_subject" class="form-label">Subject / Topic:</label>
                <input type="text" class="form-control" id="feedback_subject" name="feedback_subject" required>
            </div>
            <div class="mb-3">
                <label for="feedback_message" class="form-label">Detailed Feedback:</label>
                <textarea class="form-control" id="feedback_message" name="feedback_message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Feedback</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

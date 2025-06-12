<?php
require_once 'php/config.php';
require_once 'php/session_check.php';
// Page specific PHP code follows
// $username = $current_username; // from session_check.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> {/* Adjust path if necessary */}
</head>
<body>
    <header>
        <h1>Dad and Dude Repair Dashboard</h1>
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
        <?php
        if (isset($_GET['status']) && $_GET['status'] === 'access_denied') { // General access denied
            echo '<div class="alert alert-danger">Access Denied: You do not have permission to view that page.</div>';
        }
        if (isset($_GET['status']) && $_GET['status'] === 'admin_access_denied') { // Specific message for admin area
            $access_denied_msg = $_SESSION['access_denied_message'] ?? 'You do not have permission to access the admin area.';
            echo '<div class="alert alert-danger">' . htmlspecialchars($access_denied_msg) . '</div>';
            unset($_SESSION['access_denied_message']); // Clear message after displaying
        }
        ?>
        <h2>Welcome, <?php echo htmlspecialchars($current_username); ?>!</h2>
        <p>This is your personalized dashboard. From here, you can access training modules, use the AI repair assistance tool, or provide feedback.</p>
        {/* More dashboard content will go here later */}
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Training Modules</h5>
                        <p class="card-text">Access tutorials, manuals, and quizzes.</p>
                        <a href="training.php" class="btn btn-primary">Go to Training</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">AI Repair Assistance</h5>
                        <p class="card-text">Get AI-powered help with your repairs.</p>
                        <a href="ai_assistance.php" class="btn btn-primary">Use AI Tool</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Submit Feedback</h5>
                        <p class="card-text">Report issues or suggest improvements.</p>
                        <a href="feedback.php" class="btn btn-primary">Give Feedback</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

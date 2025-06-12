<?php
require_once 'php/config.php'; // Session start and security check

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit;
}

$model = $_GET['model'] ?? 'Unknown Model';
$type = $_GET['type'] ?? 'video'; // Though this page is specific to videos
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Tutorial: <?php echo htmlspecialchars($model); ?> - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Video Tutorial Viewer</h1>
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
        <h2>Viewing Video Tutorial for: <?php echo htmlspecialchars($model); ?></h2>
        <p>This is a placeholder for the <?php echo htmlspecialchars($model); ?> video tutorial. An embedded video player will be here in a future update.</p>
        {/* Placeholder for embedded video, e.g., using an iframe for YouTube or a <video> tag */}
        <div class="embed-responsive embed-responsive-16by9 mt-3">
            <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe> {/* Placeholder video */}
        </div>
        <a href="training.php" class="btn btn-secondary mt-3">Back to Training Modules</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

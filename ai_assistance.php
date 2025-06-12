<?php
require_once 'php/config.php'; // Adjust path if necessary

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html"); // Adjust path if necessary
    exit;
}
$username = $_SESSION['username'];
$upload_message = '';
$analysis_result = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["laptop_image"])) {
    $target_dir = "uploads/";
    // Ensure target directory exists, create if not (basic error handling)
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $target_file = $target_dir . basename($_FILES["laptop_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["laptop_image"]["tmp_name"]);
    if ($check !== false) {
        $upload_message = "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        $upload_message = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (e.g., 5MB limit)
    if ($_FILES["laptop_image"]["size"] > 5000000) {
        $upload_message = "Sorry, your file is too large (max 5MB).";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        $upload_message = "Sorry, only JPG, JPEG, & PNG files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        $upload_message = "Sorry, your file was not uploaded. " . $upload_message;
    } else {
        if (move_uploaded_file($_FILES["laptop_image"]["tmp_name"], $target_file)) {
            $upload_message = "The file ". htmlspecialchars(basename($_FILES["laptop_image"]["name"])). " has been uploaded.";
            // Simulate AI Analysis
            $analysis_result = "<strong>Simulated AI Analysis for " . htmlspecialchars(basename($_FILES["laptop_image"]["name"])) . ":</strong><br>";
            $analysis_result .= "Detected Components: Motherboard, RAM sticks, SSD, Fan assembly.<br>";
            $analysis_result .= "Potential Issue: RAM stick in slot 1 might be unseated.<br>";
            $analysis_result .= "Suggested Step: Carefully re-seat the RAM stick in slot 1. Ensure clips are engaged.";
        } else {
            $upload_message = "Sorry, there was an error uploading your file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Repair Assistance - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>AI Repair Assistance</h1>
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
        <h2>Upload Laptop Internals Image</h2>
        <p>Upload an image of the laptop's internal components. The AI will analyze it and provide guidance.</p>

        <form action="ai_assistance.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="laptop_image" class="form-label">Select image to upload:</label>
                <input type="file" class="form-control" name="laptop_image" id="laptop_image" required>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Upload and Analyze</button>
        </form>

        <?php if (!empty($upload_message)): ?>
        <div class="alert alert-info mt-3">
            <?php echo $upload_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($analysis_result)): ?>
        <div class="alert alert-success mt-3">
            <?php echo $analysis_result; ?>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

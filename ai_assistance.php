<?php
require_once 'php/config.php';
require_once 'php/session_check.php';
require_once 'php/db_connect.php'; // For $pdo, ensure this is correctly included

$upload_message = '';
$analysis_result_html = ''; // To store formatted HTML result
$raw_analysis_json = ''; // To store raw JSON for debugging or display

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["laptop_image"])) {
    $target_dir = "uploads/"; // Make sure this directory exists and is writable
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0775, true) && !is_dir($target_dir)) {
            // Check again if directory exists after creation attempt, to handle race conditions or permission issues
            $upload_message = "Failed to create upload directory. Please check server permissions.";
            $uploadOk = 0; // Prevent further processing
        } else {
            $uploadOk = 1;
        }
    } else {
        $uploadOk = 1; // Directory already exists
    }

    if ($uploadOk) { // Proceed only if directory exists or was created
        $target_file = $target_dir . basename($_FILES["laptop_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is an actual image or fake image
        $check = @getimagesize($_FILES["laptop_image"]["tmp_name"]); // Suppress error if not an image
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $upload_message = "File is not an image or image format is not supported by getimagesize.";
            $uploadOk = 0;
        }

        // Check file size (e.g., 5MB limit)
        if ($uploadOk && $_FILES["laptop_image"]["size"] > 5 * 1024 * 1024) { // 5 MB
            $upload_message = "Sorry, your file is too large (max 5MB).";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if ($uploadOk && !in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $upload_message = "Sorry, only JPG, JPEG, & PNG files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            if(empty($upload_message)) $upload_message = "Sorry, your file could not be uploaded due to an unknown error.";
        } else {
            if (move_uploaded_file($_FILES["laptop_image"]["tmp_name"], $target_file)) {
                $uploaded_filename = basename($_FILES["laptop_image"]["name"]);
                $upload_message = "The file ". htmlspecialchars($uploaded_filename). " has been uploaded successfully.";

                // Call Python AI Service
                $python_api_url = 'http://127.0.0.1:5001/analyze_image'; // Ensure Flask app is running here

                // Data to send to Python API: just the filename (basename)
                $data_to_send = ['image_path' => $uploaded_filename];

                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($data_to_send),
                        'timeout' => 15 // Seconds
                    ],
                    // Add SSL context options if your Flask app uses HTTPS (not for 127.0.0.1 usually)
                    // 'ssl' => [
                    //     'verify_peer' => false, // Not recommended for production
                    //     'verify_peer_name' => false,
                    // ],
                ];
                $context = stream_context_create($options);

                $request_start_time = microtime(true);
                $api_response_json = @file_get_contents($python_api_url, false, $context);
                $response_time_seconds = microtime(true) - $request_start_time;

                if ($api_response_json === FALSE) {
                    $analysis_result_html = "<div class='alert alert-danger'>Failed to connect to AI analysis service or service returned an error. Please ensure the AI service is running and accessible at $python_api_url. Response time: " . number_format($response_time_seconds, 4) . "s</div>";
                    error_log("Failed to get response from Python AI service at $python_api_url. Data sent: " . json_encode($data_to_send) . ". Error: " . ($http_response_header[0] ?? 'No HTTP response header'));
                    $raw_analysis_json = 'Error: No response from AI service.';
                } else {
                    $raw_analysis_json = $api_response_json;
                    $api_response_data = json_decode($api_response_json, true);

                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($api_response_data)) {
                        $analysis_result_html = "<div class='alert alert-danger'>Invalid response format from AI service. Response time: " . number_format($response_time_seconds, 4) . "s</div>";
                        error_log("Invalid JSON response from AI service: " . $api_response_json);
                    } elseif (isset($api_response_data['error'])) {
                         $analysis_result_html = "<div class='alert alert-danger'>AI Service Error: " . htmlspecialchars($api_response_data['error']) . ". Response time: " . number_format($response_time_seconds, 4) . "s</div>";
                    } else {
                        $analysis_result_html = "<div class='alert alert-success'>Analysis Complete (Response time: " . number_format($response_time_seconds, 4) . "s)</div>";
                        $analysis_result_html .= "<h4>Analysis for: " . htmlspecialchars($api_response_data['filename'] ?? 'N/A') . "</h4>";

                        if(isset($api_response_data['pillow_analysis'])) {
                            $pa = $api_response_data['pillow_analysis'];
                            $analysis_result_html .= "<p><strong>Pillow Lib Analysis:</strong> Format: " . htmlspecialchars($pa['format'] ?? 'N/A') .
                                                     ", Size: " . htmlspecialchars(is_array($pa['size (width, height)']) ? implode('x', $pa['size (width, height)']) : ($pa['size (width, height)'] ?? 'N/A')) .
                                                     ", Mode: " . htmlspecialchars($pa['mode'] ?? 'N/A') . "</p>";
                        }
                        if(isset($api_response_data['opencv_analysis'])) {
                            $oa = $api_response_data['opencv_analysis'];
                            $cv_dims_text = 'N/A';
                            if(is_array($oa['dimensions (height, width)']) && count($oa['dimensions (height, width)']) >= 2){
                                $cv_dims_text = $oa['dimensions (height, width)'][0].'x'.$oa['dimensions (height, width)'][1];
                            }
                            $analysis_result_html .= "<p><strong>OpenCV Analysis:</strong> Dimensions (HxW): " . htmlspecialchars($cv_dims_text) .
                                                     ". " . htmlspecialchars($oa['info'] ?? '') . "</p>";
                        }
                        if(isset($api_response_data['simulated_ai_output'])) {
                            $sao = $api_response_data['simulated_ai_output'];
                            $analysis_result_html .= "<h5>Simulated AI Component Detection:</h5>";
                            if(!empty($sao['detected_components']) && is_array($sao['detected_components'])){
                                $analysis_result_html .= "<ul>";
                                foreach($sao['detected_components'] as $comp){
                                    $comp_name = htmlspecialchars($comp['name'] ?? 'Unknown');
                                    $comp_status = htmlspecialchars($comp['status'] ?? 'N/A');
                                    $comp_conf = htmlspecialchars(($comp['confidence']*100 ?? 0)) . "%";
                                    $analysis_result_html .= "<li>{$comp_name}: {$comp_status} (Confidence: {$comp_conf})</li>";
                                }
                                $analysis_result_html .= "</ul>";
                            }
                            if(!empty($sao['suggested_steps']) && is_array($sao['suggested_steps'])){
                                $analysis_result_html .= "<h6>Suggested Steps:</h6><ol>";
                                foreach($sao['suggested_steps'] as $step){
                                    $analysis_result_html .= "<li>" . htmlspecialchars($step) . "</li>";
                                }
                                $analysis_result_html .= "</ol>";
                            }
                             $analysis_result_html .= "<p><small>" . htmlspecialchars($sao['message'] ?? '') . "</small></p>";
                        }
                    }
                }
                // Store analysis request details in DB
                if (isset($pdo)) { // Check if $pdo is available from db_connect.php (via session_check.php)
                    try {
                        $stmt = $pdo->prepare("INSERT INTO ai_analysis_requests (user_id, image_filename, image_path, analysis_result_text, analysis_status, response_time_seconds) VALUES (:user_id, :image_filename, :image_path, :analysis_result_text, :analysis_status, :response_time_seconds)");
                        $status_db = ($api_response_json !== FALSE && isset($api_response_data) && !isset($api_response_data['error'])) ? 'completed' : 'failed';
                        $stmt->execute([
                            ':user_id' => $_SESSION['user_id'], // Assumes user_id is in session
                            ':image_filename' => $uploaded_filename,
                            ':image_path' => $target_file,
                            ':analysis_result_text' => $raw_analysis_json,
                            ':analysis_status' => $status_db,
                            ':response_time_seconds' => $response_time_seconds
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error saving AI analysis request to DB: " . $e->getMessage());
                        // Optionally, inform the user or handle silently
                        $analysis_result_html .= "<p class='text-muted'><small>Could not save analysis log to database.</small></p>";
                    }
                } else {
                     error_log("PDO object not available in ai_assistance.php. DB logging skipped.");
                }

            } else {
                $upload_message = "Sorry, there was an error moving the uploaded file. Check server permissions for 'uploads/' directory.";
                 error_log("Failed to move uploaded file: " . ($_FILES["laptop_image"]["error"] ?? 'Unknown error'));
            }
        }
    } // End of $uploadOk check for directory creation
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
        <h2>Upload Laptop Internals Image for AI Analysis</h2>
        <p>Upload an image of the laptop's internal components. The AI will analyze it and provide guidance.</p>

        <form action="ai_assistance.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="laptop_image" class="form-label">Select image to upload (JPG, PNG, max 5MB):</label>
                <input type="file" class="form-control" name="laptop_image" id="laptop_image" required accept=".jpg,.jpeg,.png">
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Upload and Analyze</button>
        </form>

        <?php if (!empty($upload_message)): ?>
        <div class="alert alert-info mt-3">
            <?php echo $upload_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($analysis_result_html)): ?>
        <div class="mt-4 card">
            <div class="card-header">AI Analysis Result</div>
            <div class="card-body">
                <?php echo $analysis_result_html; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($raw_analysis_json)): ?>
        <div class="mt-3">
            <label for="raw_json_output" class="form-label">Raw JSON Response (for debugging):</label>
            <textarea class="form-control" id="raw_json_output" rows="5" readonly><?php echo htmlspecialchars($raw_analysis_json); ?></textarea>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

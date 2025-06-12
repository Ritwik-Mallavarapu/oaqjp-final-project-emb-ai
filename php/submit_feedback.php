<?php
require_once 'config.php'; // Includes session_start()

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // If not logged in, could redirect to login or show an error.
    // For simplicity, we'll just prevent submission if somehow accessed without session.
    $_SESSION['feedback_message'] = "Error: You must be logged in to submit feedback.";
    header("Location: ../feedback.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['username'];
    $subject = trim($_POST['feedback_subject'] ?? 'No Subject');
    $message = trim($_POST['feedback_message'] ?? '');

    if (empty($message)) {
        $_SESSION['feedback_message'] = "Feedback message cannot be empty.";
    } else {
        $feedback_entry = "-----------------------------------------------------
";
        $feedback_entry .= "Date: " . date("Y-m-d H:i:s") . "
";
        $feedback_entry .= "User: " . $username . "
";
        $feedback_entry .= "Subject: " . $subject . "
";
        $feedback_entry .= "Message: " . $message . "
";
        $feedback_entry .= "-----------------------------------------------------

";

        // Define the log file path (ensure php directory is writable by the server)
        $feedback_file = 'feedback_log.txt'; // Storing in the php directory for this example

        if (file_put_contents($feedback_file, $feedback_entry, FILE_APPEND | LOCK_EX)) {
            $_SESSION['feedback_message'] = "Thank you! Your feedback has been submitted successfully.";
        } else {
            $_SESSION['feedback_message'] = "Error: Could not save your feedback. Please try again later.";
        }
    }
} else {
    // Not a POST request, redirect back or show error
    $_SESSION['feedback_message'] = "Invalid request method.";
}

header("Location: ../feedback.php"); // Redirect back to the feedback page
exit;
?>

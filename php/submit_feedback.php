<?php
require_once 'config.php'; // Includes session_start()
require_once 'db_connect.php'; // Provides $pdo

// Default message assuming something goes wrong or direct access
$_SESSION['feedback_message'] = "An unexpected error occurred, or feedback submission was invalid.";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST['feedback_subject'] ?? 'No Subject');
    $message_content = trim($_POST['feedback_message'] ?? ''); // Renamed to avoid conflict with $message session var

    if (empty($message_content)) {
        $_SESSION['feedback_message'] = "Feedback message cannot be empty.";
    } else {
        $user_id = null;
        $submitter_username = 'Anonymous'; // Default if not logged in or no username in session
        $submitter_email = null; // Placeholder for email if we add it to the form

        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            $user_id = $_SESSION['user_id'] ?? null;
            if (isset($_SESSION['username'])) {
                $submitter_username = $_SESSION['username'];
            }
            // Optionally, get email from users table if needed, or from session if stored there post-login
            if (isset($_SESSION['email'])) {
                 $submitter_email = $_SESSION['email']; // Assuming email is stored in session after login
            }
        }
        // If you want to ensure email is always captured, even for logged-out users,
        // you'd need an email field on the feedback form itself.
        // For now, we use session email if available for logged-in user.

        try {
            $sql = "INSERT INTO feedback (user_id, submitter_username, submitter_email, subject, message, submitted_at)
                    VALUES (:user_id, :submitter_username, :submitter_email, :subject, :message, NOW())";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // user_id can be null
            $stmt->bindParam(':submitter_username', $submitter_username);
            $stmt->bindParam(':submitter_email', $submitter_email); // submitter_email can be null
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message_content);

            if ($stmt->execute()) {
                $_SESSION['feedback_message'] = "Thank you! Your feedback has been submitted successfully.";
            } else {
                $_SESSION['feedback_message'] = "Error: Could not save your feedback. Please try again later.";
                error_log("Feedback submission failed during DB insert for user_id: " . ($user_id ?? 'Anonymous'));
            }
        } catch (PDOException $e) {
            error_log("PDOException in submit_feedback.php: " . $e->getMessage());
            $_SESSION['feedback_message'] = "Database error during feedback submission. Please try again later.";
        }
    }
} else {
    // Not a POST request
    $_SESSION['feedback_message'] = "Invalid request method for feedback submission.";
}

header("Location: ../feedback.php"); // Redirect back to the feedback page
exit;
?>

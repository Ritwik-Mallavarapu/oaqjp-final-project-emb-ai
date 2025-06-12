<?php
require_once 'config.php';
require_once 'session_check.php'; // Ensures user is logged in, provides $current_user_id
require_once 'db_connect.php';    // Provides $pdo

$_SESSION['progress_feedback_message'] = 'An error occurred while updating progress.'; // Default error
$_SESSION['progress_feedback_type'] = 'danger';
$return_url = $_POST['return_url'] ?? '../training.php'; // Default redirect

if ($_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST['content_id'], $_POST['content_type'], $_POST['action'])) {

    $content_id = filter_input(INPUT_POST, 'content_id', FILTER_VALIDATE_INT);
    $content_type = trim($_POST['content_type']); // 'manual' or 'video'
    $action = trim($_POST['action']); // 'mark_complete' or 'mark_incomplete'

    if (!$content_id || !in_array($content_type, ['manual', 'video'])) {
        $_SESSION['progress_feedback_message'] = "Invalid content specified for progress update.";
        header("Location: " . $return_url);
        exit;
    }

    $new_status = ($action == 'mark_complete') ? 'completed' : 'not_started'; // Or 'in_progress' if needed
    $completed_at_value = ($new_status == 'completed') ? date("Y-m-d H:i:s") : null;

    try {
        // Use ON DUPLICATE KEY UPDATE to insert or update progress
        $sql = "INSERT INTO user_progress (user_id, content_type, content_id, status, completed_at, last_accessed_at)
                VALUES (:user_id, :content_type, :content_id, :status, :completed_at, NOW())
                ON DUPLICATE KEY UPDATE status = VALUES(status), completed_at = VALUES(completed_at), last_accessed_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $current_user_id,
            ':content_type' => $content_type,
            ':content_id' => $content_id,
            ':status' => $new_status,
            ':completed_at' => $completed_at_value
        ]);

        if ($new_status == 'completed') {
            $_SESSION['progress_feedback_message'] = ucfirst($content_type) . " marked as completed!";
        } else {
            $_SESSION['progress_feedback_message'] = ucfirst($content_type) . " completion undone.";
        }
        $_SESSION['progress_feedback_type'] = 'success';

    } catch (PDOException $e) {
        error_log("PDOException in mark_progress.php: " . $e->getMessage());
        $_SESSION['progress_feedback_message'] = "Database error while updating progress for " . htmlspecialchars($content_type) . ".";
        $_SESSION['progress_feedback_type'] = 'danger';
    }
} else {
    $_SESSION['progress_feedback_message'] = "Invalid request for progress update.";
}

header("Location: " . $return_url);
exit;
?>

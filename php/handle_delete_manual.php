<?php
// php/handle_delete_manual.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */ ) {
//    $_SESSION['manual_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_manuals.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_id'])) {
    $manual_id = filter_input(INPUT_POST, 'manual_id', FILTER_VALIDATE_INT);

    if (!$manual_id) {
        $_SESSION['manual_op_success_message'] = "Invalid manual ID for deletion.";
        header("Location: ../admin_manage_manuals.php");
        exit;
    }

    try {
        // First, get the file path to delete the actual file
        $stmt_select = $pdo->prepare("SELECT file_path, title FROM training_manuals WHERE id = :id");
        $stmt_select->bindParam(':id', $manual_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $manual = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if ($manual) {
            $file_path_on_server = '../' . $manual['file_path']; // Path relative to this script

            // Delete from database
            $stmt_delete = $pdo->prepare("DELETE FROM training_manuals WHERE id = :id");
            $stmt_delete->bindParam(':id', $manual_id, PDO::PARAM_INT);

            if ($stmt_delete->execute()) {
                // If DB deletion is successful, delete the file
                if (!empty($manual['file_path']) && file_exists($file_path_on_server)) {
                    if (!unlink($file_path_on_server)) {
                         // Log error if file deletion failed, but proceed with success message for DB deletion
                        error_log("Failed to delete manual file: " . $file_path_on_server . " for manual ID: " . $manual_id);
                        $_SESSION['manual_op_success_message'] = "Manual '".htmlspecialchars($manual['title'])."' deleted from database, but its PDF file could not be removed from server. Please check server logs/permissions.";
                    } else {
                         $_SESSION['manual_op_success_message'] = "Training manual '".htmlspecialchars($manual['title'])."' and its PDF file deleted successfully!";
                    }
                } else {
                    $_SESSION['manual_op_success_message'] = "Training manual '".htmlspecialchars($manual['title'])."' deleted successfully (no associated file found or path was empty).";
                }
            } else {
                $_SESSION['manual_op_success_message'] = "Failed to delete manual from database.";
            }
        } else {
            $_SESSION['manual_op_success_message'] = "Manual not found for deletion (ID: $manual_id).";
        }

    } catch (PDOException $e) {
        error_log("PDOException in handle_delete_manual.php: " . $e->getMessage());
        // Check for foreign key constraint violation (e.g., if quizzes or progress link to it)
        if ($e->getCode() == '23000') { // Integrity constraint violation
             $_SESSION['manual_op_success_message'] = "Cannot delete manual. It might be linked to existing quizzes or user progress. Please remove those links first. (DB Error)";
        } else {
            $_SESSION['manual_op_success_message'] = "Database error while deleting manual. " . $e->getMessage();
        }
    }

    header("Location: ../admin_manage_manuals.php");
    exit;

} else {
    $_SESSION['manual_op_success_message'] = "Invalid request for deleting manual.";
    header("Location: ../admin_manage_manuals.php");
    exit;
}
?>

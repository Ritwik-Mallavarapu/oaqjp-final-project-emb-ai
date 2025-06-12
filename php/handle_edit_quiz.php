<?php
// php/handle_edit_quiz.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */) {
//    $_SESSION['quiz_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_quizzes.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quiz_id'])) {
    $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    $manual_id = filter_input(INPUT_POST, 'manual_id', FILTER_VALIDATE_INT);
    if (empty($manual_id)) { $manual_id = null; }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? null);

    $_SESSION['quiz_form_data'] = $_POST; // Store for repopulation on error

    if (!$quiz_id) {
        $_SESSION['quiz_form_error'] = "Quiz ID is missing for update.";
        header("Location: ../admin_edit_quiz.php?id=" . ($quiz_id ?: '')); // May not have quiz_id if it was missing
        exit;
    }
    if (empty($title)) {
        $_SESSION['quiz_form_error'] = "Quiz Title is a required field.";
        header("Location: ../admin_edit_quiz.php?id=" . $quiz_id);
        exit;
    }

    try {
        $sql = "UPDATE quizzes SET
                manual_id = :manual_id,
                title = :title,
                description = :description
                WHERE id = :quiz_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':manual_id' => $manual_id,
            ':title' => $title,
            ':description' => $description,
            ':quiz_id' => $quiz_id
        ]);

        $_SESSION['quiz_op_success_message'] = "Quiz '".htmlspecialchars($title)."' updated successfully!";
        unset($_SESSION['quiz_form_data']);
        header("Location: ../admin_manage_quizzes.php");
        exit;

    } catch (PDOException $e) {
        error_log("PDOException in handle_edit_quiz.php: " . $e->getMessage());
        $_SESSION['quiz_form_error'] = "Database error while updating quiz: " . $e->getMessage();
        header("Location: ../admin_edit_quiz.php?id=" . $quiz_id);
        exit;
    }
} else {
    $_SESSION['quiz_op_success_message'] = "Invalid request for editing quiz.";
    header("Location: ../admin_manage_quizzes.php");
    exit;
}
?>

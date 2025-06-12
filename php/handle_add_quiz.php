<?php
// php/handle_add_quiz.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */) {
//    $_SESSION['quiz_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_quizzes.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $manual_id = filter_input(INPUT_POST, 'manual_id', FILTER_VALIDATE_INT);
    if (empty($manual_id)) { // Allow empty string from select to become NULL
        $manual_id = null;
    }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? null);

    $_SESSION['quiz_form_data'] = $_POST; // Store for repopulation on error

    if (empty($title)) {
        $_SESSION['quiz_form_error'] = "Quiz Title is a required field.";
        header("Location: ../admin_add_quiz.php");
        exit;
    }

    try {
        $sql = "INSERT INTO quizzes (manual_id, title, description, created_at)
                VALUES (:manual_id, :title, :description, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':manual_id' => $manual_id, // PDO handles null correctly
            ':title' => $title,
            ':description' => $description
        ]);

        $_SESSION['quiz_op_success_message'] = "Quiz '".htmlspecialchars($title)."' added successfully! You can now add questions to it.";
        unset($_SESSION['quiz_form_data']);
        header("Location: ../admin_manage_quizzes.php"); // Or redirect to manage questions for this new quiz
        exit;

    } catch (PDOException $e) {
        error_log("PDOException in handle_add_quiz.php: " . $e->getMessage());
        $_SESSION['quiz_form_error'] = "Database error while adding quiz: " . $e->getMessage();
        header("Location: ../admin_add_quiz.php");
        exit;
    }
} else {
    header("Location: ../admin_add_quiz.php"); // Should not happen if form is used
    exit;
}
?>

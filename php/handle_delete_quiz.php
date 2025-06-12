<?php
// php/handle_delete_quiz.php
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

    if (!$quiz_id) {
        $_SESSION['quiz_op_success_message'] = "Invalid quiz ID for deletion.";
        header("Location: ../admin_manage_quizzes.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get quiz title for feedback message BEFORE deleting
        $stmt_title = $pdo->prepare("SELECT title FROM quizzes WHERE id = :quiz_id");
        $stmt_title->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_title->execute();
        $quiz_title_data = $stmt_title->fetch();
        $quiz_title = $quiz_title_data ? $quiz_title_data['title'] : "ID " . $quiz_id;


        // 1. Delete answers for questions in this quiz
        // Need to get question_ids first
        $stmt_q_ids = $pdo->prepare("SELECT id FROM quiz_questions WHERE quiz_id = :quiz_id");
        $stmt_q_ids->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_q_ids->execute();
        $question_ids = $stmt_q_ids->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($question_ids)) {
            $in_clause_q_ids = implode(',', array_fill(0, count($question_ids), '?'));
            $stmt_del_ans = $pdo->prepare("DELETE FROM quiz_answers WHERE question_id IN ($in_clause_q_ids)");
            $stmt_del_ans->execute($question_ids);
        }

        // 2. Delete questions for this quiz
        $stmt_del_q = $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id = :quiz_id");
        $stmt_del_q->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_del_q->execute();

        // 3. Delete user progress for this quiz (optional, consider if this data should be kept/anonymized)
        // For now, let's delete it to maintain integrity if quiz is gone.
        $stmt_del_prog = $pdo->prepare("DELETE FROM user_progress WHERE content_type = 'quiz' AND content_id = :quiz_id");
        $stmt_del_prog->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_del_prog->execute();

        // 4. Delete the quiz itself
        $stmt_del_quiz = $pdo->prepare("DELETE FROM quizzes WHERE id = :quiz_id");
        $stmt_del_quiz->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);

        if ($stmt_del_quiz->execute()) {
            $pdo->commit();
            $_SESSION['quiz_op_success_message'] = "Quiz '".htmlspecialchars($quiz_title)."' and all its associated questions, answers, and user progress deleted successfully.";
        } else {
            $pdo->rollBack();
            $_SESSION['quiz_op_success_message'] = "Failed to delete quiz '".htmlspecialchars($quiz_title)."'.";
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDOException in handle_delete_quiz.php: " . $e->getMessage());
        if ($e->getCode() == '23000') { // Integrity constraint (should be caught by manual cascade)
             $_SESSION['quiz_op_success_message'] = "Cannot delete quiz '".htmlspecialchars($quiz_title)."'. It might be linked in a way not handled by cascade (e.g. user progress if not deleted above). (DB Error)";
        } else {
            $_SESSION['quiz_op_success_message'] = "Database error while deleting quiz '".htmlspecialchars($quiz_title)."'. " . $e->getMessage();
        }
    }

    header("Location: ../admin_manage_quizzes.php");
    exit;

} else {
    $_SESSION['quiz_op_success_message'] = "Invalid request for deleting quiz.";
    header("Location: ../admin_manage_quizzes.php");
    exit;
}
?>

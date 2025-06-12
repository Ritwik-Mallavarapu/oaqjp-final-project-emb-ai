<?php
// php/handle_delete_question.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */) {
//    $_SESSION['question_op_success_message'] = "Invalid request (CSRF).";
//    $quiz_id_redirect = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
//    header("Location: " . ($quiz_id_redirect ? "../admin_manage_quiz_questions.php?quiz_id=$quiz_id_redirect" : "../admin_manage_quizzes.php"));
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

$question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT); // For redirect

if (!$question_id || !$quiz_id) {
    $_SESSION['question_op_success_message'] = "Question or Quiz ID missing for deletion.";
    header("Location: " . ($quiz_id ? "../admin_manage_quiz_questions.php?quiz_id=$quiz_id" : "../admin_manage_quizzes.php"));
    exit;
}
$redirect_url = "../admin_manage_quiz_questions.php?quiz_id=" . $quiz_id;

try {
    $pdo->beginTransaction();

    // Delete answers associated with the question
    $stmt_del_ans = $pdo->prepare("DELETE FROM quiz_answers WHERE question_id = :question_id");
    $stmt_del_ans->execute([':question_id' => $question_id]);

    // Delete the question itself
    $stmt_del_q = $pdo->prepare("DELETE FROM quiz_questions WHERE id = :question_id AND quiz_id = :quiz_id");
    $stmt_del_q->execute([':question_id' => $question_id, ':quiz_id' => $quiz_id]);

    if ($stmt_del_q->rowCount() > 0) {
        $pdo->commit();
        $_SESSION['question_op_success_message'] = "Question (ID: $question_id) and its answers deleted successfully.";
    } else {
        $pdo->rollBack();
        $_SESSION['question_op_success_message'] = "Failed to delete question (ID: $question_id). It might have already been deleted or does not belong to this quiz.";
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("PDOException in handle_delete_question.php: " . $e->getMessage());
    $_SESSION['question_op_success_message'] = "Database error while deleting question: " . $e->getMessage();
}

header("Location: " . $redirect_url);
exit;
?>

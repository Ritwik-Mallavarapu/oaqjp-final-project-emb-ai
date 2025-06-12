<?php
// php/handle_edit_question.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */) {
//    $_SESSION['question_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_quizzes.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

$question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT); // For redirect and context

// Store form data for repopulation on error
$_SESSION['question_edit_form_data'] = $_POST;


if (!$question_id || !$quiz_id) {
    $_SESSION['question_op_success_message'] = "Question or Quiz ID missing for update.";
    header("Location: " . ($quiz_id ? "../admin_manage_quiz_questions.php?quiz_id=$quiz_id" : "../admin_manage_quizzes.php"));
    exit;
}
$redirect_url = "../admin_manage_quiz_questions.php?quiz_id=" . $quiz_id;
$edit_redirect_url = "../admin_edit_question.php?id=$question_id&quiz_id=$quiz_id";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = trim($_POST['question_type'] ?? '');
    $order_num = filter_input(INPUT_POST, 'order_num', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
    $answers_data = $_POST['answers'] ?? []; // Array of answers
    $correct_answer_index_radio = $_POST['correct_answer_index'] ?? null; // For single_choice/true_false

    // Validation (similar to add question)
    if (empty($question_text) || empty($question_type) || !in_array($question_type, ['single_choice', 'multiple_choice', 'true_false'])) {
        $_SESSION['question_edit_form_error'] = "Question text and a valid type are required.";
        header("Location: " . $edit_redirect_url);
        exit;
    }
    if (count($answers_data) < 2) {
        $_SESSION['question_edit_form_error'] = "At least two answer options are required.";
         header("Location: " . $edit_redirect_url);
        exit;
    }
    // Further validation for answers text and correctness (as in handle_add_question)
    $correct_answer_found_validation = false;
    foreach($answers_data as $idx => $ans_item) {
        if (empty(trim($ans_item['text']))) {
             $_SESSION['question_edit_form_error'] = "All answer options must have text.";
             header("Location: " . $edit_redirect_url);
             exit;
        }
        if ($question_type === 'single_choice' || $question_type === 'true_false') {
            if ($correct_answer_index_radio !== null && (int)$correct_answer_index_radio == $idx) $correct_answer_found_validation = true;
        } else { // multiple_choice
            if (isset($ans_item['is_correct']) && $ans_item['is_correct'] == '1') $correct_answer_found_validation = true;
        }
    }
     if (!$correct_answer_found_validation) {
        $_SESSION['question_edit_form_error'] = "At least one answer must be marked as correct.";
        header("Location: " . $edit_redirect_url);
        exit;
    }


    try {
        $pdo->beginTransaction();

        // Update question details
        $sql_update_q = "UPDATE quiz_questions SET question_text = :text, question_type = :type, order_num = :order WHERE id = :id AND quiz_id = :quiz_id";
        $stmt_update_q = $pdo->prepare($sql_update_q);
        $stmt_update_q->execute([
            ':text' => $question_text,
            ':type' => $question_type,
            ':order' => $order_num,
            ':id' => $question_id,
            ':quiz_id' => $quiz_id
        ]);

        // Strategy: Delete existing answers, then re-insert submitted ones.
        $stmt_delete_ans = $pdo->prepare("DELETE FROM quiz_answers WHERE question_id = :question_id");
        $stmt_delete_ans->execute([':question_id' => $question_id]);

        // Re-insert answers
        $sql_insert_a = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (:qid, :text, :correct)";
        $stmt_insert_a = $pdo->prepare($sql_insert_a);

        foreach ($answers_data as $idx => $answer_item) {
            $is_correct_flag = 0;
            if ($question_type === 'single_choice' || $question_type === 'true_false') {
                if ($correct_answer_index_radio !== null && (int)$correct_answer_index_radio == $idx) {
                    $is_correct_flag = 1;
                }
            } else { // multiple_choice
                $is_correct_flag = (isset($answer_item['is_correct']) && $answer_item['is_correct'] == '1') ? 1 : 0;
            }

            $stmt_insert_a->execute([
                ':qid' => $question_id,
                ':text' => trim($answer_item['text']),
                ':correct' => $is_correct_flag
            ]);
        }

        $pdo->commit();
        $_SESSION['question_op_success_message'] = "Question (ID: $question_id) updated successfully!";
        unset($_SESSION['question_edit_form_data']); // Clear form data on success
        header("Location: " . $redirect_url ); // Redirect to manage questions page
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDOException in handle_edit_question.php: " . $e->getMessage());
        $_SESSION['question_edit_form_error'] = "Database error while updating question: " . $e->getMessage();
        header("Location: " . $edit_redirect_url); // Redirect back to edit page on error
        exit;
    }
} else {
    $_SESSION['question_op_success_message'] = "Invalid request.";
    header("Location: " . ($quiz_id ? "../admin_manage_quiz_questions.php?quiz_id=$quiz_id" : "../admin_manage_quizzes.php"));
    exit;
}
?>

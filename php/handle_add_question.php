<?php
// php/handle_add_question.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */) {
//    $_SESSION['question_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_quizzes.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);

// Store form data for repopulation on error
$_SESSION['question_form_data'] = $_POST;


if (!$quiz_id) {
    $_SESSION['question_op_success_message'] = "Quiz ID missing for adding question.";
    header("Location: ../admin_manage_quizzes.php");
    exit;
}
// Redirect back to the specific quiz's question management page on error/success
$redirect_url = "../admin_manage_quiz_questions.php?quiz_id=" . $quiz_id;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = trim($_POST['question_type'] ?? '');
    $order_num = filter_input(INPUT_POST, 'order_num', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
    $answers_data = $_POST['answers'] ?? []; // Array of answers, each with 'text' and optional 'is_correct'

    if (empty($question_text) || empty($question_type) || !in_array($question_type, ['single_choice', 'multiple_choice', 'true_false'])) {
        $_SESSION['question_form_error'] = "Question text and a valid type are required.";
        header("Location: " . $redirect_url);
        exit;
    }
    if (count($answers_data) < 2) {
        $_SESSION['question_form_error'] = "At least two answer options are required for a question.";
        header("Location: " . $redirect_url);
        exit;
    }

    // Validate answers and correctness based on type
    $correct_answer_found = false;
    $correct_answer_index = $_POST['correct_answer_index'] ?? null; // For radio types

    foreach ($answers_data as $idx => $answer) {
        if (empty(trim($answer['text']))) {
            $_SESSION['question_form_error'] = "All answer options must have text.";
            header("Location: " . $redirect_url);
            exit;
        }
        if ($question_type === 'single_choice' || $question_type === 'true_false') {
            if ($correct_answer_index !== null && (int)$correct_answer_index === $idx) {
                $correct_answer_found = true;
            }
        } else { // multiple_choice
            if (isset($answer['is_correct']) && $answer['is_correct'] == '1') {
                $correct_answer_found = true;
            }
        }
    }

    if (!$correct_answer_found && ($question_type === 'single_choice' || $question_type === 'true_false')) {
         // For radio, correct_answer_index must be set
        if ($correct_answer_index === null) {
            $_SESSION['question_form_error'] = "One answer must be marked as correct for single choice or T/F questions.";
            header("Location: " . $redirect_url);
            exit;
        }
    } elseif (!$correct_answer_found && $question_type === 'multiple_choice'){
         $_SESSION['question_form_error'] = "At least one answer must be marked as correct for multiple choice questions.";
         header("Location: " . $redirect_url);
         exit;
    }


    try {
        $pdo->beginTransaction();

        // Insert question
        $sql_q = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, order_num)
                  VALUES (:quiz_id, :question_text, :question_type, :order_num)";
        $stmt_q = $pdo->prepare($sql_q);
        $stmt_q->execute([
            ':quiz_id' => $quiz_id,
            ':question_text' => $question_text,
            ':question_type' => $question_type,
            ':order_num' => $order_num
        ]);
        $question_id = $pdo->lastInsertId();

        // Insert answers
        $sql_a = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (:question_id, :answer_text, :is_correct)";
        $stmt_a = $pdo->prepare($sql_a);

        foreach ($answers_data as $idx => $answer_item) {
            $is_correct_flag = 0;
            if ($question_type === 'single_choice' || $question_type === 'true_false') {
                if ($correct_answer_index !== null && (int)$correct_answer_index === $idx) {
                    $is_correct_flag = 1;
                }
            } else { // multiple_choice
                $is_correct_flag = (isset($answer_item['is_correct']) && $answer_item['is_correct'] == '1') ? 1 : 0;
            }

            $stmt_a->execute([
                ':question_id' => $question_id,
                ':answer_text' => trim($answer_item['text']),
                ':is_correct' => $is_correct_flag
            ]);
        }

        $pdo->commit();
        $_SESSION['question_op_success_message'] = "New question and its answers added successfully!";
        unset($_SESSION['question_form_data']); // Clear form data on success

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDOException in handle_add_question.php: " . $e->getMessage());
        $_SESSION['question_form_error'] = "Database error while adding question: " . $e->getMessage();
    }

    header("Location: " . $redirect_url);
    exit;
} else {
    $_SESSION['question_op_success_message'] = "Invalid request."; // To manage quizzes if quiz_id somehow lost
    header("Location: ../admin_manage_quizzes.php");
    exit;
}
?>

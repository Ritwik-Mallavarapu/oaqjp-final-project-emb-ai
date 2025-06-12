<?php
require_once 'config.php';
require_once 'session_check.php'; // Ensures user is logged in, provides $current_user_id
require_once 'db_connect.php';    // Provides $pdo

$_SESSION['quiz_feedback_message'] = 'An error occurred while submitting your quiz.'; // Default error
$_SESSION['quiz_feedback_type'] = 'danger';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quiz_id'])) {
    $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    $submitted_answers = $_POST['answers'] ?? []; // Array of [question_id => answer_id] or [question_id => [answer_id1, answer_id2]]

    if (!$quiz_id) {
        $_SESSION['quiz_feedback_message'] = "Invalid quiz submission. Quiz ID missing.";
        header("Location: ../quiz.php"); // Or a generic error page
        exit;
    }

    try {
        // Fetch all questions for the quiz and their correct answers
        $stmt_correct = $pdo->prepare(
            "SELECT q.id AS question_id, q.question_type, GROUP_CONCAT(a.id ORDER BY a.id) AS correct_answer_ids
             FROM quiz_questions q
             JOIN quiz_answers a ON q.id = a.question_id
             WHERE q.quiz_id = :quiz_id AND a.is_correct = 1
             GROUP BY q.id, q.question_type"
        );
        $stmt_correct->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_correct->execute();
        $correct_answers_map = $stmt_correct->fetchAll(PDO::FETCH_KEY_PAIR); // question_id => 'id1,id2' or just 'id'

        $total_questions = count($correct_answers_map);
        $score = 0;

        if ($total_questions > 0) {
            foreach ($correct_answers_map as $question_id => $correct_ids_str) {
                $correct_ids_array = explode(',', $correct_ids_str);
                sort($correct_ids_array); // Ensure consistent order for comparison

                $user_answer_for_q = $submitted_answers[$question_id] ?? null;

                if ($user_answer_for_q !== null) {
                    if (is_array($user_answer_for_q)) { // Multiple choice
                        sort($user_answer_for_q);
                        if ($user_answer_for_q == $correct_ids_array) {
                            $score++;
                        }
                    } else { // Single choice / True-False
                        if (count($correct_ids_array) == 1 && $user_answer_for_q == $correct_ids_array[0]) {
                            $score++;
                        }
                    }
                }
            }
            $percentage_score = ($score / $total_questions) * 100;
        } else {
            $percentage_score = 0; // Or handle as "quiz has no scorable questions"
            $_SESSION['quiz_feedback_message'] = "This quiz has no questions with correct answers defined, so it cannot be scored.";
             // No need to store progress if quiz is empty/unscorable in this way
            header("Location: ../quiz.php?quiz_id=" . $quiz_id);
            exit;
        }

        // Store in user_progress
        // Check if an entry already exists, if so, update? Or allow multiple attempts?
        // For now, let's assume we insert a new attempt each time.
        // A more robust system might update if a previous attempt for this quiz by this user exists and
        // perhaps only keep the highest score or latest attempt.
        $stmt_insert_progress = $pdo->prepare(
            "INSERT INTO user_progress (user_id, content_type, content_id, status, score, completed_at)
             VALUES (:user_id, 'quiz', :content_id, 'completed', :score, NOW())
             ON DUPLICATE KEY UPDATE status='completed', score=VALUES(score), completed_at=NOW()" // Example: Update if already taken
        );
        $stmt_insert_progress->execute([
            ':user_id' => $current_user_id,
            ':content_id' => $quiz_id,
            ':score' => $percentage_score
        ]);

        $_SESSION['quiz_feedback_message'] = "Quiz submitted! Your score: " . htmlspecialchars(number_format($percentage_score, 2)) . "% ($score / $total_questions correct).";
        $_SESSION['quiz_feedback_type'] = 'success';

    } catch (PDOException $e) {
        error_log("PDOException in submit_quiz.php: " . $e->getMessage());
        $_SESSION['quiz_feedback_message'] = "Database error while submitting quiz. Please try again.";
        $_SESSION['quiz_feedback_type'] = 'danger';
    }

    header("Location: ../quiz.php?quiz_id=" . $quiz_id); // Redirect back to the quiz page to show score
    exit;

} else {
    // Not a POST request or quiz_id missing
    $_SESSION['quiz_feedback_message'] = "Invalid access to quiz submission.";
    $_SESSION['quiz_feedback_type'] = 'danger';
    header("Location: ../training.php"); // Redirect to training overview
    exit;
}
?>

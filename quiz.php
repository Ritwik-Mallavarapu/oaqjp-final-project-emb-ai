<?php
require_once 'php/config.php';
require_once 'php/session_check.php'; // Ensures user is logged in and provides $current_user_id
require_once 'php/db_connect.php';    // Provides $pdo

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$quiz = null;
$questions = [];
$error_message = '';
$manual_id_for_quiz = null; // To link back to a manual if applicable

if (!$quiz_id) {
    // Alternative: if manual_id is passed, find the first quiz for that manual
    $manual_id_param = filter_input(INPUT_GET, 'manual_id', FILTER_VALIDATE_INT);
    if ($manual_id_param) {
        try {
            $stmt_find_quiz = $pdo->prepare("SELECT id FROM quizzes WHERE manual_id = :manual_id ORDER BY id LIMIT 1");
            $stmt_find_quiz->bindParam(':manual_id', $manual_id_param, PDO::PARAM_INT);
            $stmt_find_quiz->execute();
            $quiz_data_temp = $stmt_find_quiz->fetch();
            if ($quiz_data_temp) {
                $quiz_id = $quiz_data_temp['id'];
                $manual_id_for_quiz = $manual_id_param; // Store for context
            } else {
                $error_message = "No quiz found for the specified training module.";
            }
        } catch (PDOException $e) {
            error_log("PDOException in quiz.php (finding quiz by manual_id): " . $e->getMessage());
            $error_message = "Error finding quiz. Please try again later.";
        }
    } else {
        $error_message = "No quiz ID specified.";
    }
}


if ($quiz_id && empty($error_message)) {
    try {
        // Fetch quiz details
        $stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = :quiz_id");
        $stmt_quiz->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt_quiz->execute();
        $quiz = $stmt_quiz->fetch();

        if (!$quiz) {
            $error_message = "Quiz not found.";
        } else {
            if (!$manual_id_for_quiz && $quiz['manual_id']) { // If quiz_id was passed directly but it has a manual_id
                $manual_id_for_quiz = $quiz['manual_id'];
            }
            // Fetch questions and their answers
            $stmt_questions = $pdo->prepare(
                "SELECT q.id AS question_id, q.question_text, q.question_type,
                        a.id AS answer_id, a.answer_text
                 FROM quiz_questions q
                 JOIN quiz_answers a ON q.id = a.question_id
                 WHERE q.quiz_id = :quiz_id
                 ORDER BY q.order_num, q.id, a.id"
            );
            $stmt_questions->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
            $stmt_questions->execute();

            $results = $stmt_questions->fetchAll();
            if (empty($results)){
                 $error_message = "This quiz currently has no questions.";
            } else {
                foreach ($results as $row) {
                    if (!isset($questions[$row['question_id']])) {
                        $questions[$row['question_id']] = [
                            'text' => $row['question_text'],
                            'type' => $row['question_type'],
                            'answers' => []
                        ];
                    }
                    $questions[$row['question_id']]['answers'][] = [
                        'id' => $row['answer_id'],
                        'text' => $row['answer_text']
                    ];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("PDOException in quiz.php: " . $e->getMessage());
        $error_message = "Error fetching quiz details. Please try again later.";
        $quiz = null; // Ensure quiz is null on error
        $questions = []; // Ensure questions are empty on error
    }
}

// Check for quiz submission feedback
$feedback_message = $_SESSION['quiz_feedback_message'] ?? null;
$feedback_type = $_SESSION['quiz_feedback_type'] ?? 'info'; // 'info', 'success', 'danger'
unset($_SESSION['quiz_feedback_message'], $_SESSION['quiz_feedback_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $quiz ? htmlspecialchars($quiz['title']) : 'Quiz'; ?> - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1><?php echo $quiz ? htmlspecialchars($quiz['title']) : 'Quiz'; ?></h1>
    </header>
    <nav>
        <ul class="nav justify-content-center bg-dark">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="training.php">Training Modules</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="ai_assistance.php">AI Repair Assistance</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="php/logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <?php if ($feedback_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_type); ?> mt-3"><?php echo htmlspecialchars($feedback_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif ($quiz && !empty($questions)): ?>
            <p class="lead"><?php echo nl2br(htmlspecialchars($quiz['description'] ?? '')); ?></p>
            <hr>
            <form action="php/submit_quiz.php" method="post">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                <?php foreach ($questions as $q_id => $question): ?>
                    <div class="mb-4">
                        <h5><?php echo htmlspecialchars($question['text']); ?></h5>
                        <?php foreach ($question['answers'] as $answer): ?>
                            <div class="form-check">
                                <?php if ($question['type'] == 'single_choice' || $question['type'] == 'true_false'): ?>
                                    <input class="form-check-input" type="radio"
                                           name="answers[<?php echo $q_id; ?>]"
                                           id="answer_<?php echo $answer['id']; ?>"
                                           value="<?php echo $answer['id']; ?>" required>
                                <?php elseif ($question['type'] == 'multiple_choice'): ?>
                                    <input class="form-check-input" type="checkbox"
                                           name="answers[<?php echo $q_id; ?>][]"
                                           id="answer_<?php echo $answer['id']; ?>"
                                           value="<?php echo $answer['id']; ?>">
                                <?php endif; ?>
                                <label class="form-check-label" for="answer_<?php echo $answer['id']; ?>">
                                    <?php echo htmlspecialchars($answer['text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Submit Quiz</button>
            </form>
        <?php elseif ($quiz && empty($questions) && empty($error_message)): // Quiz exists but has no questions, and no other error occurred ?>
             <div class="alert alert-info mt-3">This quiz ("<?php echo htmlspecialchars($quiz['title']); ?>") currently has no questions. Please check back later.</div>
        <?php endif; // $error_message handles other cases like quiz not found ?>

        <hr>
        <a href="training.php" class="btn btn-secondary mt-3 mb-3">Back to Training Modules</a>
        <?php if ($manual_id_for_quiz): ?>
            <a href="manual_viewer.php?id=<?php echo $manual_id_for_quiz; ?>" class="btn btn-info mt-3 mb-3">View Associated Manual</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

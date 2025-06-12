<?php
// admin_manage_quiz_questions.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
require_once 'php/db_connect.php'; // For DB operations

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$quiz = null;
$questions_with_answers = []; // Store questions and their answers
$page_error = '';
$success_message = $_SESSION['question_op_success_message'] ?? null;
unset($_SESSION['question_op_success_message']);
$error_message_form = $_SESSION['question_form_error'] ?? null;
$form_data = $_SESSION['question_form_data'] ?? [];
unset($_SESSION['question_form_error'], $_SESSION['question_form_data']);


if (!$quiz_id) {
    $_SESSION['quiz_op_success_message'] = "No quiz ID specified to manage questions.";
    header("Location: admin_manage_quizzes.php");
    exit;
}

try {
    // Fetch quiz details for context
    $stmt_quiz = $pdo->prepare("SELECT id, title FROM quizzes WHERE id = :quiz_id");
    $stmt_quiz->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt_quiz->execute();
    $quiz = $stmt_quiz->fetch();

    if (!$quiz) {
        $_SESSION['quiz_op_success_message'] = "Quiz not found (ID: $quiz_id).";
        header("Location: admin_manage_quizzes.php");
        exit;
    }

    // Fetch existing questions for this quiz and their answers
    $stmt_questions = $pdo->prepare(
        "SELECT qq.id AS question_id, qq.question_text, qq.question_type, qq.order_num,
                qa.id AS answer_id, qa.answer_text, qa.is_correct
         FROM quiz_questions qq
         LEFT JOIN quiz_answers qa ON qq.id = qa.question_id
         WHERE qq.quiz_id = :quiz_id
         ORDER BY qq.order_num, qq.id, qa.id"
    );
    $stmt_questions->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt_questions->execute();
    $results = $stmt_questions->fetchAll();

    foreach ($results as $row) {
        if (!isset($questions_with_answers[$row['question_id']])) {
            $questions_with_answers[$row['question_id']] = [
                'text' => $row['question_text'],
                'type' => $row['question_type'],
                'order' => $row['order_num'],
                'answers' => []
            ];
        }
        if ($row['answer_id']) { // Only add answer if it exists
            $questions_with_answers[$row['question_id']]['answers'][] = [
                'id' => $row['answer_id'],
                'text' => $row['answer_text'],
                'is_correct' => $row['is_correct']
            ];
        }
    }

} catch (PDOException $e) {
    error_log("PDOException in admin_manage_quiz_questions.php: " . $e->getMessage());
    $page_error = "Error fetching quiz/question data: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions for "<?php echo htmlspecialchars($quiz['title'] ?? 'Quiz'); ?>"</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .answer-field { margin-bottom: 10px; }
        .answer-field input[type="text"] { margin-right: 10px; }
    </style>
</head>
<body>
    <header><h1>Manage Questions for Quiz: "<?php echo htmlspecialchars($quiz['title'] ?? ''); ?>"</h1></header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                 <ul class="navbar-nav me-auto">
                    <li><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li><a class="nav-link" href="admin_view_users.php">User Performance</a></li>
                    <li><a class="nav-link" href="admin_manage_manuals.php">Manage Manuals</a></li>
                    <li><a class="nav-link active" aria-current="page" href="admin_manage_quizzes.php">Manage Quizzes</a></li>
                </ul>
                <ul class="navbar-nav ms-auto"><li><a class="nav-link" href="php/logout.php">Logout</a></li></ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($page_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($page_error); ?></div><?php endif; ?>
        <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message_form): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message_form); ?></div><?php endif; ?>

        <!-- Add New Question Form -->
        <div class="card mb-4">
            <div class="card-header"><h4>Add New Question</h4></div>
            <div class="card-body">
                <form action="php/handle_add_question.php" method="post">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                    <input type="hidden" name="csrf_token" value="temp_csrf_token_question_add">

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($form_data['question_text'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="question_type" name="question_type" required>
                                <option value="single_choice" <?php echo (isset($form_data['question_type']) && $form_data['question_type'] == 'single_choice') ? 'selected' : ''; ?>>Single Choice (Radio Buttons)</option>
                                <option value="multiple_choice" <?php echo (isset($form_data['question_type']) && $form_data['question_type'] == 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice (Checkboxes)</option>
                                <option value="true_false" <?php echo (isset($form_data['question_type']) && $form_data['question_type'] == 'true_false') ? 'selected' : ''; ?>>True/False</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="order_num" class="form-label">Order (Optional, e.g., 1, 2, 3)</label>
                            <input type="number" class="form-control" id="order_num" name="order_num" value="<?php echo htmlspecialchars($form_data['order_num'] ?? '0'); ?>" min="0">
                        </div>
                    </div>

                    <div id="answers_container" class="mb-3">
                        <label class="form-label">Answers <span class="text-danger">*</span> (Provide at least 2 answers. For True/False, typically "True" and "False")</label>
                        <!-- JavaScript will add answer fields here -->
                    </div>
                    <button type="button" id="add_answer_btn" class="btn btn-secondary btn-sm mb-3">Add Answer Option</button>

                    <button type="submit" class="btn btn-primary">Add Question</button>
                </form>
            </div>
        </div>
        <hr/>

        <!-- List Existing Questions -->
        <h4>Existing Questions for "<?php echo htmlspecialchars($quiz['title']); ?>"</h4>
        <?php if (empty($questions_with_answers) && !$page_error): ?>
            <p>No questions added to this quiz yet.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($questions_with_answers as $q_id => $question_data): ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start mb-2">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($question_data['order'] . ". " . $question_data['text']); ?></h5>
                            <small>Type: <?php echo htmlspecialchars(str_replace('_', ' ', $question_data['type'])); ?></small>
                        </div>
                        <ul class="list-unstyled ms-3">
                            <?php foreach ($question_data['answers'] as $answer): ?>
                                <li>
                                    <?php echo htmlspecialchars($answer['text']); ?>
                                    <?php if ($answer['is_correct']): ?>
                                        <span class="badge bg-success ms-1">Correct</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                             <?php if (empty($question_data['answers'])): ?><li>No answers defined.</li><?php endif; ?>
                        </ul>
                        <small>
                            <a href="admin_edit_question.php?id=<?php echo $q_id; ?>&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                            <form action="php/handle_delete_question.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this question and all its answers?');">
                                <input type="hidden" name="question_id" value="<?php echo $q_id; ?>">
                                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>"> <!-- For redirect -->
                                <input type="hidden" name="csrf_token" value="temp_csrf_token_q_delete">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="mt-4"><a href="admin_manage_quizzes.php" class="btn btn-secondary">Back to Quizzes List</a></div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const answersContainer = document.getElementById('answers_container');
    const addAnswerBtn = document.getElementById('add_answer_btn');
    const questionTypeSelect = document.getElementById('question_type');
    let answerCount = 0;

    // Function to add a new answer field
    function addAnswerField(answerText = '', isCorrect = false) {
        answerCount++;
        const div = document.createElement('div');
        div.classList.add('row', 'gx-2', 'mb-2', 'align-items-center', 'answer-field-row');

        const type = questionTypeSelect.value;
        let inputType = 'checkbox'; // Default for multiple_choice
        let inputName = 'answers[' + (answerCount-1) + '][is_correct]';

        if (type === 'single_choice' || type === 'true_false') {
            inputType = 'radio';
            // For radio, all in the group must have the same name for 'is_correct'
            // but values will differentiate. We use value '1' for the selected correct one.
            // The text input will be `answer_text[index]`
            // The radio for correctness `correct_answer_index` value `index`
        }

        let correctInputHtml = '';
        if (type === 'single_choice' || type === 'true_false') {
             correctInputHtml = \`
                <div class="col-auto">
                    <input class="form-check-input" type="radio" name="correct_answer_index" id="correct_answer_\${answerCount}" value="\${answerCount-1}" \${isCorrect ? 'checked' : ''} required>
                    <label class="form-check-label" for="correct_answer_\${answerCount}">Correct</label>
                </div>
            \`;
        } else { // multiple_choice
            correctInputHtml = \`
                <div class="col-auto">
                    <input class="form-check-input" type="checkbox" name="answers[\${answerCount-1}][is_correct]" id="correct_answer_\${answerCount}" value="1" \${isCorrect ? 'checked' : ''}>
                    <label class="form-check-label" for="correct_answer_\${answerCount}">Correct</label>
                </div>
            \`;
        }

        div.innerHTML = \`
            <div class="col">
                <input type="text" class="form-control form-control-sm" name="answers[\${answerCount-1}][text]" placeholder="Answer Text \${answerCount}" value="\${answerText}" required>
            </div>
            \${correctInputHtml}
            <div class="col-auto">
                <button type="button" class="btn btn-danger btn-sm remove-answer-btn">X</button>
            </div>
        \`;
        answersContainer.appendChild(div);
    }

    // Add initial answer fields (e.g., 2 for most types, or for True/False)
    function setupInitialAnswers() {
        // Clear existing answer fields before adding new ones
        answersContainer.querySelectorAll('.answer-field-row').forEach(row => row.remove());
        answerCount = 0;

        const type = questionTypeSelect.value;
        if (type === 'true_false') {
            addAnswerField('True', true); // Default True to be correct for new T/F
            addAnswerField('False', false);
            addAnswerBtn.style.display = 'none'; // Hide "Add Answer" for T/F
        } else {
            // Add 2 default empty fields for single/multiple choice
            addAnswerField('', true); // Default first answer to be correct
            addAnswerField('', false);
            addAnswerBtn.style.display = 'inline-block'; // Show "Add Answer"
        }
    }

    addAnswerBtn.addEventListener('click', function() {
        const type = questionTypeSelect.value;
        if (type !== 'true_false') { // Don't add more for T/F
             addAnswerField(); // Add empty answer field
        }
    });

    // Handle removal of an answer field
    answersContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-answer-btn')) {
            // Prevent removing if it's one of the last two for non-T/F, or any for T/F
            const type = questionTypeSelect.value;
            const currentAnswerFields = answersContainer.querySelectorAll('.answer-field-row').length;
            if (type === 'true_false' || currentAnswerFields > 2) {
                 e.target.closest('.answer-field-row').remove();
            } else {
                alert("A question must have at least two answer options.");
            }
        }
    });

    questionTypeSelect.addEventListener('change', setupInitialAnswers);

    // Initialize based on current selection (or defaults)
    // If form_data exists (from error redirect), repopulate answers
    const existingAnswers = <?php echo json_encode($form_data['answers'] ?? []); ?>;
    if (existingAnswers && existingAnswers.length > 0) {
        existingAnswers.forEach(ans => {
            let isCorrectVal = false;
            if (questionTypeSelect.value === 'single_choice' || questionTypeSelect.value === 'true_false') {
                // This part is tricky because correct_answer_index is a single value.
                // For now, this repopulation might not perfectly set the checked radio on error.
                // A more robust solution would pass back the correct_answer_index from PHP.
                // Let's assume if 'is_correct' was submitted as part of answers array, it means something.
                 isCorrectVal = (ans.is_correct == '1' || (isset($form_data['correct_answer_index']) && $form_data['correct_answer_index'] == answerCount)); // Simplified
            } else {
                 isCorrectVal = (ans.is_correct == '1');
            }
            addAnswerField(ans.text, isCorrectVal);
        });
        // If correct_answer_index was set for radio types
        if ( (questionTypeSelect.value === 'single_choice' || questionTypeSelect.value === 'true_false') && <?php echo isset($form_data['correct_answer_index']) ? 'true' : 'false'; ?>) {
            const correctIdx = parseInt(<?php echo json_encode($form_data['correct_answer_index'] ?? -1); ?>);
            const radios = answersContainer.querySelectorAll('input[name="correct_answer_index"]');
            if (radios[correctIdx]) {
                radios[correctIdx].checked = true;
            }
        }


    } else {
        setupInitialAnswers(); // Setup default fields if no form_data
    }
});
</script>
</body>
</html>

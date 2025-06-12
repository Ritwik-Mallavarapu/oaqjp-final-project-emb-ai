<?php
// admin_edit_question.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
require_once 'php/db_connect.php'; // For DB operations

$question_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); // For context and redirect

$question_data = null;
$answers_data = [];
$quiz_title = '';
$page_error = '';

if (!$question_id || !$quiz_id) {
    $_SESSION['question_op_success_message'] = "Invalid question or quiz ID for editing.";
    header("Location: admin_manage_quizzes.php"); // Fallback to main quiz list
    exit;
}

// Fetch Quiz Title for display
try {
    $stmt_quiz_title = $pdo->prepare("SELECT title FROM quizzes WHERE id = :quiz_id");
    $stmt_quiz_title->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt_quiz_title->execute();
    $quiz_title_res = $stmt_quiz_title->fetch();
    if ($quiz_title_res) $quiz_title = $quiz_title_res['title']; else throw new Exception("Quiz not found.");
} catch (Exception $e) {
     $_SESSION['question_op_success_message'] = "Parent quiz not found.";
    header("Location: admin_manage_quizzes.php");
    exit;
}


// Fetch existing question and its answers
try {
    $stmt_q = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = :question_id AND quiz_id = :quiz_id");
    $stmt_q->execute([':question_id' => $question_id, ':quiz_id' => $quiz_id]);
    $question_data = $stmt_q->fetch(PDO::FETCH_ASSOC);

    if (!$question_data) {
        $_SESSION['question_op_success_message'] = "Question not found or does not belong to the specified quiz.";
        header("Location: admin_manage_quiz_questions.php?quiz_id=" . $quiz_id);
        exit;
    }

    $stmt_a = $pdo->prepare("SELECT id, answer_text, is_correct FROM quiz_answers WHERE question_id = :question_id ORDER BY id");
    $stmt_a->bindParam(':question_id', $question_id, PDO::PARAM_INT);
    $stmt_a->execute();
    $answers_data = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("PDOException in admin_edit_question.php (fetch): " . $e->getMessage());
    $page_error = "Error fetching question data: " . $e->getMessage();
}

$error_message_form = $_SESSION['question_edit_form_error'] ?? null;
// Use session data for repopulation if it exists (meaning there was a submission error)
$form_question_text = $_SESSION['question_edit_form_data']['question_text'] ?? $question_data['question_text'] ?? '';
$form_question_type = $_SESSION['question_edit_form_data']['question_type'] ?? $question_data['question_type'] ?? 'single_choice';
$form_order_num = $_SESSION['question_edit_form_data']['order_num'] ?? $question_data['order_num'] ?? '0';
// For answers, if session form data has answers, use them, otherwise use fetched answers.
$form_answers = $_SESSION['question_edit_form_data']['answers'] ?? $answers_data;
$form_correct_answer_index = $_SESSION['question_edit_form_data']['correct_answer_index'] ?? null;

if ($form_correct_answer_index === null && ($form_question_type === 'single_choice' || $form_question_type === 'true_false')) {
    foreach ($answers_data as $idx => $ans) {
        if ($ans['is_correct']) {
            $form_correct_answer_index = $idx;
            break;
        }
    }
}

unset($_SESSION['question_edit_form_error'], $_SESSION['question_edit_form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question for "<?php echo htmlspecialchars($quiz_title); ?>"</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>.answer-field { margin-bottom: 10px; }</style>
</head>
<body>
    <header><h1>Edit Question (ID: <?php echo $question_id; ?>)</h1><p class="text-muted">For Quiz: "<?php echo htmlspecialchars($quiz_title); ?>"</p></header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
             <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
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
        <?php if ($error_message_form): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message_form); ?></div><?php endif; ?>

        <?php if ($question_data && !$page_error): ?>
        <form action="php/handle_edit_question.php" method="post">
            <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            <input type="hidden" name="csrf_token" value="temp_csrf_token_q_edit">

            <div class="mb-3">
                <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($form_question_text); ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="question_type_edit" class="form-label">Question Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="question_type_edit" name="question_type" required>
                        <option value="single_choice" <?php echo ($form_question_type == 'single_choice') ? 'selected' : ''; ?>>Single Choice</option>
                        <option value="multiple_choice" <?php echo ($form_question_type == 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                        <option value="true_false" <?php echo ($form_question_type == 'true_false') ? 'selected' : ''; ?>>True/False</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="order_num" class="form-label">Order</label>
                    <input type="number" class="form-control" id="order_num" name="order_num" value="<?php echo htmlspecialchars($form_order_num); ?>" min="0">
                </div>
            </div>

            <div id="answers_container_edit" class="mb-3">
                <label class="form-label">Answers <span class="text-danger">*</span></label>
                <!-- JS will populate this -->
            </div>
            <button type="button" id="add_answer_btn_edit" class="btn btn-secondary btn-sm mb-3">Add Answer Option</button>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="admin_manage_quiz_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Cancel</a>
        </form>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const answersContainer = document.getElementById('answers_container_edit');
    const addAnswerBtn = document.getElementById('add_answer_btn_edit');
    const questionTypeSelect = document.getElementById('question_type_edit');
    let answerCount = 0; // Used to give unique IDs to new fields if needed

    // Initial answer data from PHP (for editing)
    let currentAnswers = <?php echo json_encode($form_answers); ?>;
    let initialCorrectAnswerIndex = <?php echo json_encode($form_correct_answer_index); ?>;


    function renderAnswerField(answer = { text: '', is_correct: false }, index) {
        const div = document.createElement('div');
        div.classList.add('row', 'gx-2', 'mb-2', 'align-items-center', 'answer-field-row');
        const currentQuestionType = questionTypeSelect.value;

        let textValue = answer.answer_text !== undefined ? answer.answer_text : (answer.text || '');
        let isCorrectValue = answer.is_correct == '1' || answer.is_correct === true;

        let correctInputHtml = '';
        if (currentQuestionType === 'single_choice' || currentQuestionType === 'true_false') {
            // For radio, need to check if this index matches the initialCorrectAnswerIndex
            let isChecked = (initialCorrectAnswerIndex !== null && parseInt(initialCorrectAnswerIndex) === index);
             // If current answer from DB is correct and initialCorrectAnswerIndex wasn't set from form_data, set it.
            if (isCorrectValue && initialCorrectAnswerIndex === null) {
                 isChecked = true;
                 initialCorrectAnswerIndex = index; // Keep track of the DB correct choice for initial radio state.
            }


            correctInputHtml = \`
                <div class="col-auto">
                    <input class="form-check-input" type="radio" name="correct_answer_index" id="correct_answer_edit_\${index}" value="\${index}" \${isChecked ? 'checked' : ''} required>
                    <label class="form-check-label" for="correct_answer_edit_\${index}">Correct</label>
                </div>
            \`;
        } else { // multiple_choice
            correctInputHtml = \`
                <div class="col-auto">
                    <input class="form-check-input" type="checkbox" name="answers[\${index}][is_correct]" id="correct_answer_edit_\${index}" value="1" \${isCorrectValue ? 'checked' : ''}>
                    <label class="form-check-label" for="correct_answer_edit_\${index}">Correct</label>
                </div>
            \`;
        }

        div.innerHTML = \`
            <div class="col">
                <input type="text" class="form-control form-control-sm" name="answers[\${index}][text]" placeholder="Answer Text" value="\${textValue}" required>
                <input type="hidden" name="answers[\${index}][id]" value="\${answer.id || ''}"> {/* For existing answers */}
            </div>
            \${correctInputHtml}
            <div class="col-auto">
                <button type="button" class="btn btn-danger btn-sm remove-answer-btn-edit">X</button>
            </div>
        \`;
        answersContainer.appendChild(div);
        answerCount = Math.max(answerCount, index + 1); // Ensure answerCount is correctly set for new additions
    }

    function rebuildAnswerFields() {
        answersContainer.innerHTML = '<label class="form-label">Answers <span class="text-danger">*</span></label>'; // Reset container and re-add label
        const type = questionTypeSelect.value;

        // Reset initialCorrectAnswerIndex if type changed to multiple_choice, or if it was from a previous type.
        // Or, if currentAnswers already reflects the form_data from a previous POST, this index might be from there.
        if (type === 'multiple_choice' && initialCorrectAnswerIndex !== null && typeof initialCorrectAnswerIndex !== 'object') {
            // If we switched to multiple_choice, the single 'correct_answer_index' is not directly applicable in the same way.
            // We rely on the 'is_correct' property of each answer.
        } else if ( (type === 'single_choice' || type === 'true_false') && initialCorrectAnswerIndex === null) {
            // If switching to single/TF and no correct index is known (e.g. from form_data), find or default.
            let foundCorrect = false;
            currentAnswers.forEach((ans, idx) => {
                if (ans.is_correct == '1' || ans.is_correct === true) {
                    initialCorrectAnswerIndex = idx;
                    foundCorrect = true;
                }
            });
            if (!foundCorrect && currentAnswers.length > 0) initialCorrectAnswerIndex = 0; // Default to first if none marked
        }


        if (type === 'true_false') {
            let trueAnswer = currentAnswers.find(a => a.answer_text?.toLowerCase() === 'true') || { text: 'True', is_correct: (initialCorrectAnswerIndex === 0 || (initialCorrectAnswerIndex === null && currentAnswers.length > 0 && currentAnswers[0].is_correct)) };
            let falseAnswer = currentAnswers.find(a => a.answer_text?.toLowerCase() === 'false') || { text: 'False', is_correct: (initialCorrectAnswerIndex === 1 || (currentAnswers.length > 1 && currentAnswers[1].is_correct)) };

            // Determine which is correct if not explicitly set by form_data
            if (initialCorrectAnswerIndex === null) { // If not set by form data error
                if (trueAnswer.is_correct) initialCorrectAnswerIndex = 0;
                else if (falseAnswer.is_correct) initialCorrectAnswerIndex = 1;
                else initialCorrectAnswerIndex = 0; // Default true
            }

            renderAnswerField(trueAnswer, 0);
            renderAnswerField(falseAnswer, 1);
            addAnswerBtn.style.display = 'none';
        } else {
            currentAnswers.forEach((ans, idx) => {
                renderAnswerField(ans, idx);
            });
             // Ensure at least two answers for single/multiple choice if currentAnswers is empty
            if (currentAnswers.length === 0) {
                renderAnswerField({text: '', is_correct: true}, 0);
                renderAnswerField({text: '', is_correct: false}, 1);
                if (type === 'single_choice') initialCorrectAnswerIndex = 0;
            } else if (currentAnswers.length === 1 && type !== 'true_false') {
                 renderAnswerField({text: '', is_correct: false}, currentAnswers.length);
            }
            addAnswerBtn.style.display = 'inline-block';
        }
    }

    addAnswerBtn.addEventListener('click', function() {
        if (questionTypeSelect.value !== 'true_false') {
            renderAnswerField({ text: '', is_correct: false, id: '' }, answerCount);
        }
    });

    answersContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-answer-btn-edit')) {
            const type = questionTypeSelect.value;
            const currentAnswerFields = answersContainer.querySelectorAll('.answer-field-row').length;
            if (type === 'true_false' || currentAnswerFields > 2) {
                e.target.closest('.answer-field-row').remove();
            } else {
                alert("A question must have at least two answer options.");
            }
        }
    });

    questionTypeSelect.addEventListener('change', function() {
        // When type changes, it's complex. Simplest: clear and add defaults.
        // User will need to re-enter answers if they change type.
        // Reset initialCorrectAnswerIndex before rebuilding.
        initialCorrectAnswerIndex = null;
        // Preserve texts if possible? For now, let's reset based on type.
        if (this.value === 'true_false') {
            currentAnswers = []; // Will be populated by True/False defaults
        } else if (this.value === 'single_choice') {
            currentAnswers = currentAnswers.map(a => ({...a, is_correct: false})); // Unset all, default first later
            if (currentAnswers.length > 0) initialCorrectAnswerIndex = 0; else initialCorrectAnswerIndex = null;
        } else { // multiple_choice
            // is_correct is per answer, so no single index
        }
        rebuildAnswerFields();
    });

    // Initial render of answer fields
    rebuildAnswerFields();
});
</script>
</body>
</html>

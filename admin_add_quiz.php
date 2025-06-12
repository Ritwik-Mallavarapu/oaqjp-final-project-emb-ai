<?php
// admin_add_quiz.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
require_once 'php/db_connect.php'; // For fetching manuals list for dropdown

$manuals = [];
try {
    $stmt_manuals = $pdo->query("SELECT id, brand, model, title FROM training_manuals ORDER BY brand, model, title");
    $manuals = $stmt_manuals->fetchAll();
} catch (PDOException $e) {
    error_log("PDOException in admin_add_quiz.php (fetching manuals): " . $e->getMessage());
    // Non-fatal, form can still be shown but manual dropdown will be empty.
}

$error_message = $_SESSION['quiz_form_error'] ?? null;
$form_data = $_SESSION['quiz_form_data'] ?? [];
unset($_SESSION['quiz_form_error'], $_SESSION['quiz_form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Quiz - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header><h1>Add New Quiz</h1></header>
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
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <form action="php/handle_add_quiz.php" method="post">
            <input type="hidden" name="csrf_token" value="temp_csrf_token_quiz_add">

            <div class="mb-3">
                <label for="manual_id" class="form-label">Associated Training Manual (Optional)</label>
                <select class="form-select" id="manual_id" name="manual_id">
                    <option value="">None (General Quiz)</option>
                    <?php foreach ($manuals as $manual): ?>
                        <option value="<?php echo $manual['id']; ?>" <?php echo (isset($form_data['manual_id']) && $form_data['manual_id'] == $manual['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($manual['brand'] . " " . $manual['model'] . " - " . $manual['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Quiz</button>
            <a href="admin_manage_quizzes.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

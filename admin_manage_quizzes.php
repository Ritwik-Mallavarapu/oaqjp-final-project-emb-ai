<?php
// admin_manage_quizzes.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
require_once 'php/db_connect.php'; // For fetching quiz data

$quizzes = [];
$error_message = '';
$success_message = $_SESSION['quiz_op_success_message'] ?? null;
unset($_SESSION['quiz_op_success_message']);

try {
    $sql = "SELECT q.id, q.title, q.description, q.created_at, tm.title AS manual_title, tm.brand, tm.model
            FROM quizzes q
            LEFT JOIN training_manuals tm ON q.manual_id = tm.id
            ORDER BY tm.brand, tm.model, q.title";
    $stmt = $pdo->query($sql);
    $quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("PDOException in admin_manage_quizzes.php: " . $e->getMessage());
    $error_message = "Error fetching quizzes. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <header><h1>Manage Quizzes</h1></header>
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
        <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Existing Quizzes</h3>
            <a href="admin_add_quiz.php" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Add New Quiz</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Quiz Title</th>
                        <th>Associated Manual</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quizzes) && !$error_message): ?>
                        <tr><td colspan="6">No quizzes found. Start by adding a new one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['id']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                <td><?php echo $quiz['manual_title'] ? htmlspecialchars($quiz['manual_title'] . " (" . $quiz['brand'] . " " . $quiz['model'] . ")") : 'N/A (General Quiz)'; ?></td>
                                <td><?php echo htmlspecialchars(substr($quiz['description'] ?? '', 0, 70) . (strlen($quiz['description'] ?? '') > 70 ? '...' : '')); ?></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($quiz['created_at']))); ?></td>
                                <td>
                                    <a href="admin_manage_quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-info btn-sm" title="Manage Questions"><i class="bi bi-card-list"></i> Questions</a>
                                    <a href="admin_edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm" title="Edit Quiz Details"><i class="bi bi-pencil-square"></i></a>
                                    <form action="php/handle_delete_quiz.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this quiz AND ALL ITS QUESTIONS/ANSWERS? This is irreversible.');">
                                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="temp_csrf_token_quiz_delete">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Quiz"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

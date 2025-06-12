<?php
// admin_manage_manuals.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.

require_once 'php/db_connect.php'; // Provides $pdo
$manuals = [];
$error_message = '';
$success_message = $_SESSION['manual_op_success_message'] ?? null; // For success messages from add/edit/delete
unset($_SESSION['manual_op_success_message']);


try {
    $stmt = $pdo->query("SELECT id, brand, model, title, file_path, created_at, updated_at FROM training_manuals ORDER BY brand, model, title");
    $manuals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("PDOException in admin_manage_manuals.php: " . $e->getMessage());
    $error_message = "Error fetching training manuals. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Training Manuals - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <!-- Optional: Bootstrap Icons for edit/delete icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <header>
        <h1>Manage Training Manuals</h1>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_view_users.php">User Performance</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="admin_manage_manuals.php">Manage Manuals</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_quizzes.php">Manage Quizzes</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="php/logout.php">Logout (<?php echo htmlspecialchars($current_username); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Existing Training Manuals</h3>
            <a href="admin_add_manual.php" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Add New Manual</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Title</th>
                        <th>File Path</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($manuals) && !$error_message): ?>
                        <tr><td colspan="8">No training manuals found. Start by adding a new one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($manuals as $manual): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($manual['id']); ?></td>
                                <td><?php echo htmlspecialchars($manual['brand']); ?></td>
                                <td><?php echo htmlspecialchars($manual['model']); ?></td>
                                <td><?php echo htmlspecialchars($manual['title']); ?></td>
                                <td>
                                    <?php if ($manual['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($manual['file_path']); ?>" target="_blank">View PDF</a>
                                    <?php else: echo 'N/A'; endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($manual['created_at']))); ?></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($manual['updated_at']))); ?></td>
                                <td>
                                    <a href="admin_edit_manual.php?id=<?php echo $manual['id']; ?>" class="btn btn-primary btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <form action="php/handle_delete_manual.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this manual? This action cannot be undone.');">
                                        <input type="hidden" name="manual_id" value="<?php echo $manual['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php /* Generate and echo CSRF token here */ echo 'temp_csrf_token'; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash-fill"></i></button>
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

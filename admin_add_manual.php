<?php
// admin_add_manual.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
// No db_connect needed here directly, only in the handler.

$error_message = $_SESSION['manual_form_error'] ?? null;
$form_data = $_SESSION['manual_form_data'] ?? [];
unset($_SESSION['manual_form_error'], $_SESSION['manual_form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Training Manual - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header><h1>Add New Training Manual</h1></header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li><a class="nav-link" href="admin_view_users.php">User Performance</a></li>
                    <li><a class="nav-link active" aria-current="page" href="admin_manage_manuals.php">Manage Manuals</a></li>
                    <li><a class="nav-link" href="admin_manage_quizzes.php">Manage Quizzes</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="php/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="php/handle_add_manual.php" method="post" enctype="multipart/form-data">
            <!-- CSRF Token (implement properly later) -->
            <input type="hidden" name="csrf_token" value="<?php echo 'temp_csrf_token'; /* TODO: Generate CSRF token */ ?>">

            <div class="mb-3">
                <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($form_data['brand'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($form_data['model'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Manual Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="configuration_details" class="form-label">Configuration Details (Optional)</label>
                <textarea class="form-control" id="configuration_details" name="configuration_details" rows="2"><?php echo htmlspecialchars($form_data['configuration_details'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="manual_pdf" class="form-label">Manual PDF File <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="manual_pdf" name="manual_pdf" accept=".pdf" required>
                <small class="form-text text-muted">Max file size: 10MB. Only PDF files are allowed.</small>
            </div>

            <button type="submit" class="btn btn-primary">Add Manual</button>
            <a href="admin_manage_manuals.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

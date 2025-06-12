<?php
// admin_edit_manual.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.
require_once 'php/db_connect.php'; // Still needed for fetching manual data

$manual_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$manual_data = null;
$page_error = '';

if (!$manual_id) {
    $_SESSION['manual_op_success_message'] = "Invalid manual ID specified for editing.";
    header("Location: admin_manage_manuals.php"); // Redirect if no ID
    exit;
}

// Fetch existing manual data
try {
    $stmt = $pdo->prepare("SELECT * FROM training_manuals WHERE id = :id");
    $stmt->bindParam(':id', $manual_id, PDO::PARAM_INT);
    $stmt->execute();
    $manual_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$manual_data) {
        $_SESSION['manual_op_success_message'] = "Manual not found for editing (ID: $manual_id).";
        header("Location: admin_manage_manuals.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("PDOException in admin_edit_manual.php (fetch): " . $e->getMessage());
    $page_error = "Error fetching manual data for editing. Please try again.";
    // Allow page to load and display this error within the form area.
}

$error_message = $_SESSION['manual_form_error'] ?? null;
// If redirected from submission error, use session data, else use fetched DB data
$form_data = $_SESSION['manual_form_data'] ?? $manual_data;
unset($_SESSION['manual_form_error'], $_SESSION['manual_form_data']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Training Manual - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header><h1>Edit Training Manual (ID: <?php echo htmlspecialchars($manual_id); ?>)</h1></header>
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
        <?php if ($page_error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($page_error); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($manual_data && !$page_error): // Only show form if manual data was fetched successfully ?>
        <form action="php/handle_edit_manual.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="manual_id" value="<?php echo htmlspecialchars($manual_id); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo 'temp_csrf_token'; /* TODO: Generate CSRF token */ ?>">
            <input type="hidden" name="existing_pdf_path" value="<?php echo htmlspecialchars($manual_data['file_path'] ?? ''); ?>">


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
                <label for="manual_pdf" class="form-label">New Manual PDF File (Optional)</label>
                <input type="file" class="form-control" id="manual_pdf" name="manual_pdf" accept=".pdf">
                <small class="form-text text-muted">
                    Max file size: 10MB. Only PDF files.
                    <?php if (!empty($manual_data['file_path'])): ?>
                        Current file: <a href="<?php echo htmlspecialchars($manual_data['file_path']); ?>" target="_blank"><?php echo basename(htmlspecialchars($manual_data['file_path'])); ?></a>.
                        Leave empty to keep the current file.
                    <?php else: ?>
                        No current file uploaded.
                    <?php endif; ?>
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="admin_manage_manuals.php" class="btn btn-secondary">Cancel</a>
        </form>
        <?php elseif (!$page_error): // Manual not found but no DB error during fetch (should be caught by redirect earlier) ?>
            <div class="alert alert-warning">The requested manual could not be found.</div>
             <a href="admin_manage_manuals.php" class="btn btn-secondary">Back to Manage Manuals</a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

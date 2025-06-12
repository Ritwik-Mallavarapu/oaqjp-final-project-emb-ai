<?php
// admin_user_progress_detail.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.

require_once 'php/db_connect.php'; // Provides $pdo

$target_user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$target_user = null;
$user_progress_items = [];
$error_message = '';

if (!$target_user_id) {
    $error_message = "No user ID specified or invalid ID.";
} else {
    try {
        // Fetch target user's details
        $stmt_user = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = :user_id");
        $stmt_user->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
        $stmt_user->execute();
        $target_user = $stmt_user->fetch();

        if (!$target_user) {
            $error_message = "User not found.";
        } else {
            // Fetch user's progress, joining with content tables for titles
            $sql_progress = "
                SELECT
                    up.content_type,
                    up.content_id,
                    up.status,
                    up.score,
                    up.completed_at,
                    up.last_accessed_at,
                    CASE up.content_type
                        WHEN 'manual' THEN tm.title
                        WHEN 'video' THEN tv.title
                        WHEN 'quiz' THEN q.title
                        ELSE 'Unknown Content'
                    END AS content_title,
                    CASE up.content_type
                        WHEN 'manual' THEN CONCAT(tm.brand, ' ', tm.model)
                        ELSE NULL
                    END AS manual_brand_model
                FROM user_progress up
                LEFT JOIN training_manuals tm ON up.content_type = 'manual' AND up.content_id = tm.id
                LEFT JOIN training_videos tv ON up.content_type = 'video' AND up.content_id = tv.id
                LEFT JOIN quizzes q ON up.content_type = 'quiz' AND up.content_id = q.id
                WHERE up.user_id = :user_id
                ORDER BY up.last_accessed_at DESC, up.content_type
            ";
            $stmt_progress = $pdo->prepare($sql_progress);
            $stmt_progress->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
            $stmt_progress->execute();
            $user_progress_items = $stmt_progress->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("PDOException in admin_user_progress_detail.php: " . $e->getMessage());
        $error_message = "Error fetching user progress data. Please try again later.";
        $target_user = null; // Reset on error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Progress Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>User Progress Details</h1>
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="admin_view_users.php">User Performance</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_manuals.php">Manage Manuals</a></li>
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
        <?php elseif ($target_user): ?>
            <h3>Progress for: <?php echo htmlspecialchars($target_user['username']); ?>
                <small class="text-muted">(<?php echo htmlspecialchars($target_user['email']); ?>) - Role: <?php echo htmlspecialchars(ucfirst($target_user['role'])); ?></small>
            </h3>

            <?php if (empty($user_progress_items)): ?>
                <div class="alert alert-info mt-3">This user has no recorded progress yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Content Type</th>
                                <th>Content Title</th>
                                <th>Status</th>
                                <th>Score (%)</th>
                                <th>Completed At</th>
                                <th>Last Accessed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_progress_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(ucfirst($item['content_type'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($item['content_title'] ?: 'N/A'); ?>
                                        <?php if ($item['content_type'] == 'manual' && $item['manual_brand_model']): ?>
                                            <small class="text-muted d-block">(<?php echo htmlspecialchars($item['manual_brand_model']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php
                                            switch($item['status']) {
                                                case 'completed': echo 'success'; break;
                                                case 'in_progress': echo 'primary'; break;
                                                case 'not_started': echo 'secondary'; break;
                                                default: echo 'light';
                                            }?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($item['status']))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $item['content_type'] === 'quiz' && $item['score'] !== null ? htmlspecialchars(number_format($item['score'], 2)) . '%' : 'N/A'; ?></td>
                                    <td><?php echo $item['completed_at'] ? htmlspecialchars(date("Y-m-d H:i", strtotime($item['completed_at']))) : 'N/A'; ?></td>
                                    <td><?php echo $item['last_accessed_at'] ? htmlspecialchars(date("Y-m-d H:i", strtotime($item['last_accessed_at']))) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: // Should be caught by $error_message ?>
            <div class="alert alert-warning">Could not load user details.</div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="admin_view_users.php" class="btn btn-secondary">Back to User List</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

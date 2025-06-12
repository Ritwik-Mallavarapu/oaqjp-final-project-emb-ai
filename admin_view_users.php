<?php
// admin_view_users.php
require_once 'php/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.

require_once 'php/db_connect.php'; // Provides $pdo
$users = [];
$error_message = '';

try {
    // Fetch all users, could order by role or username
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY role, username");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("PDOException in admin_view_users.php: " . $e->getMessage());
    $error_message = "Error fetching user data. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User Performance - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>User Performance Overview</h1>
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
        <?php endif; ?>

        <h3>All Registered Users</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users) && !$error_message): ?>
                        <tr><td colspan="6">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge bg-<?php echo $user['role'] === 'owner' ? 'warning' : 'info'; ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($user['created_at']))); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'user'): // Only show progress for 'user' role, or adjust as needed ?>
                                        <a href="admin_user_progress_detail.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">View Progress</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                    <!-- Add other actions like Edit Role in future -->
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

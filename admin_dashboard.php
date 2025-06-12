<?php
// admin_dashboard.php
require_once 'php/admin_session_check.php';
// Now $current_user_id, $current_username, $current_user_email are available
// and owner access is confirmed.

// No need for db_connect.php on the dashboard page itself unless fetching dynamic summary data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-secondary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_view_users.php">User Performance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_manage_manuals.php">Manage Manuals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_manage_quizzes.php">Manage Quizzes</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="php/logout.php">Logout (<?php echo htmlspecialchars($current_username); ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Welcome, Owner <?php echo htmlspecialchars($current_username); ?>!</h2>
        <p>This is your administrative dashboard. From here, you can manage users, training content, and site settings.</p>

        <div class="row mt-4">
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">View User Performance</h5>
                        <p class="card-text">Track progress and quiz results for all users.</p>
                        <a href="admin_view_users.php" class="btn btn-primary">Go to User Performance</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage Training Manuals</h5>
                        <p class="card-text">Add, edit, or remove training manuals and their PDF files.</p>
                        <a href="admin_manage_manuals.php" class="btn btn-primary">Go to Manuals</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage Quizzes</h5>
                        <p class="card-text">Create new quizzes, edit existing ones, and manage questions/answers.</p>
                        <a href="admin_manage_quizzes.php" class="btn btn-primary">Go to Quizzes</a>
                    </div>
                </div>
            </div>
            <!-- Add more cards for other admin features as they are developed -->
        </div>

        <?php
        // Display access denied message if redirected here with status=access_denied_admin from another admin page attempt
        if (isset($_GET['status']) && $_GET['status'] === 'access_denied_admin') {
            echo '<div class="alert alert-danger mt-3">Access Denied: You do not have permission to view the requested admin page.</div>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'php/config.php';
require_once 'php/session_check.php';
require_once 'php/db_connect.php'; // Provides $pdo
// $username = $current_username; // from session_check.php
// $user_id = $current_user_id; // from session_check.php

$search_query = trim($_GET['search'] ?? '');
$displayed_modules = [];
$search_message = '';
$user_manual_progress = [];

$progress_feedback_message = $_SESSION['progress_feedback_message'] ?? null;
$progress_feedback_type = $_SESSION['progress_feedback_type'] ?? 'info';
unset($_SESSION['progress_feedback_message'], $_SESSION['progress_feedback_type']);

try {
    if (!empty($search_query)) {
        $sql = "SELECT id, brand, model, title, description
                FROM training_manuals
                WHERE brand LIKE :query
                   OR model LIKE :query
                   OR title LIKE :query
                   OR description LIKE :query_desc
                ORDER BY brand, model, title";
        $stmt = $pdo->prepare($sql);
        $search_param = "%" . $search_query . "%";
        $stmt->bindParam(':query', $search_param);
        $stmt->bindParam(':query_desc', $search_param); // Can be same or different if more specific search on description
    } else {
        $sql = "SELECT id, brand, model, title, description
                FROM training_manuals
                ORDER BY brand, model, title";
        $stmt = $pdo->prepare($sql);
    }
    $stmt->execute();
    $displayed_modules = $stmt->fetchAll();

    if (empty($displayed_modules) && !empty($search_query)) {
        $search_message = 'No training modules found matching your search: "' . htmlspecialchars($search_query) . '".';
    } elseif (empty($displayed_modules)) {
        $search_message = 'No training modules available at the moment. Please check back later.';
    } else {
        // Fetch progress for displayed manuals
        if (isset($current_user_id) && !empty($displayed_modules)) {
            $manual_ids_on_page = array_column($displayed_modules, 'id');
            if (!empty($manual_ids_on_page)) {
                try {
                    $sql_manual_progress = "SELECT content_id, status FROM user_progress
                                            WHERE user_id = :user_id AND content_type = 'manual' AND content_id IN (" . implode(',', array_fill(0, count($manual_ids_on_page), '?')) . ")";
                    $stmt_manual_progress = $pdo->prepare($sql_manual_progress);
                    $params_progress = array_merge([$current_user_id], $manual_ids_on_page);
                    $stmt_manual_progress->execute($params_progress);
                    while($row = $stmt_manual_progress->fetch()){
                        $user_manual_progress[$row['content_id']] = $row['status'];
                    }
                } catch (PDOException $e_progress) { // Different variable for exception
                    error_log("Error fetching manual progress for training page: " . $e_progress->getMessage());
                }
            }
        }
    }

} catch (PDOException $e) {
    error_log("PDOException in training.php: " . $e->getMessage());
    $search_message = "Error fetching training modules. Please try again later.";
    // In production, you might want to display a more generic error or handle it differently.
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Modules - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Training Modules</h1>
    </header>
    <nav>
        <ul class="nav justify-content-center bg-dark">
            <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="training.php">Training Modules</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="ai_assistance.php">AI Repair Assistance</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="php/logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($current_username); ?>! Select or Search for a Training Module.</h2>

        <form action="training.php" method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search manuals by brand, model, title..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <?php if ($progress_feedback_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($progress_feedback_type); ?> mt-3"><?php echo htmlspecialchars($progress_feedback_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($search_message)): ?>
            <div class="alert <?php echo (strpos($search_message, 'Error') !== false || strpos($search_message, 'No training modules found') !== false) ? 'alert-warning' : 'alert-info'; ?>" role="alert">
                <?php echo htmlspecialchars($search_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($displayed_modules)): ?>
            <div class="row">
                <?php foreach ($displayed_modules as $module): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($module['brand'] . ' ' . $module['model']); ?>
                                <?php if (($user_manual_progress[$module['id']] ?? null) == 'completed'): ?>
                                    <span class="badge bg-success float-end">Completed</span>
                                <?php endif; ?>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($module['title']); ?></h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($module['description'], 0, 100) . (strlen($module['description']) > 100 ? '...' : ''))); ?></p>
                            <a href="manual_viewer.php?id=<?php echo $module['id']; ?>" class="btn btn-info btn-sm mb-1">View Manual</a>

                            <!-- Links for videos and quizzes will be dynamic based on related content -->
                            <a href="video_viewer.php?manual_id=<?php echo $module['id']; ?>" class="btn btn-info btn-sm mb-1">View Videos</a>
                            <a href="quiz.php?manual_id=<?php echo $module['id']; ?>" class="btn btn-warning btn-sm mb-1">Take Quiz</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($search_message)): // Only show if no other message is already set ?>
            <div class="alert alert-info" role="alert">
                 No training modules found.
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

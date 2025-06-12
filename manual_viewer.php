<?php
require_once 'php/config.php';
require_once 'php/session_check.php';
require_once 'php/db_connect.php';
// $current_user_id, $current_username, $current_user_email are available from session_check.php

$manual_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$manual = null;
$error_message = '';
$progress_status = null;

if ($manual_id && isset($current_user_id)) {
    try {
        $stmt_progress = $pdo->prepare(
            "SELECT status FROM user_progress
             WHERE user_id = :user_id AND content_type = 'manual' AND content_id = :content_id"
        );
        $stmt_progress->execute([':user_id' => $current_user_id, ':content_id' => $manual_id]);
        $progress_row = $stmt_progress->fetch();
        if ($progress_row) {
            $progress_status = $progress_row['status'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching manual progress: " . $e->getMessage());
        // Non-critical error, proceed without progress status
    }
}

$progress_feedback_message = $_SESSION['progress_feedback_message'] ?? null;
$progress_feedback_type = $_SESSION['progress_feedback_type'] ?? 'info';
unset($_SESSION['progress_feedback_message'], $_SESSION['progress_feedback_type']);

if (!$manual_id) {
    $error_message = "Invalid manual ID specified.";
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM training_manuals WHERE id = :id");
        $stmt->bindParam(':id', $manual_id, PDO::PARAM_INT);
        $stmt->execute();
        $manual = $stmt->fetch();

        if (!$manual) {
            $error_message = "Manual not found.";
        }
    } catch (PDOException $e) {
        error_log("PDOException in manual_viewer.php: " . $e->getMessage());
        $error_message = "Error fetching manual details. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $manual ? htmlspecialchars($manual['title']) : 'Manual Viewer'; ?> - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Manual Viewer</h1>
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
        <?php if ($progress_feedback_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($progress_feedback_type); ?> mt-3"><?php echo htmlspecialchars($progress_feedback_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif ($manual): ?>
            <h2><?php echo htmlspecialchars($manual['title']); ?></h2>
            <h5 class="text-muted"><?php echo htmlspecialchars($manual['brand'] . ' ' . $manual['model']); ?></h5>
            <?php if (!empty($manual['configuration_details'])): ?>
                <p><strong>Configuration:</strong> <?php echo htmlspecialchars($manual['configuration_details']); ?></p>
            <?php endif; ?>

            <div class="mt-3">
                <strong>Description:</strong>
                <p><?php echo nl2br(htmlspecialchars($manual['description'])); ?></p>
            </div>

            <?php if (!empty($manual['file_path'])): ?>
                <div class="mt-3">
                    <strong>Manual File:</strong>
                    <p><a href="<?php echo htmlspecialchars($manual['file_path']); ?>" target="_blank" class="btn btn-primary">Download/View Manual PDF</a></p>
                    <small>Note: This link assumes the file path is a direct web-accessible link or a script handles serving the file.</small>
                </div>
            <?php elseif (!empty($manual['content_text'])): ?>
                <div class="mt-3">
                    <strong>Manual Content:</strong>
                    <div class="border p-3 bg-light">
                        <?php echo nl2br(htmlspecialchars($manual['content_text'])); // Consider using a Markdown parser if content is Markdown ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="mt-3">No specific manual file or text content available for this entry.</p>
            <?php endif; ?>

        <?php else: // Should not happen if error_message is properly set ?>
            <div class="alert alert-warning">Manual details could not be loaded.</div>
        <?php endif; ?>
        <?php if ($manual): ?>
        <hr>
        <div class="mt-3 mb-3">
           <form action="php/mark_progress.php" method="post" style="display: inline;">
               <input type="hidden" name="content_id" value="<?php echo $manual_id; ?>">
               <input type="hidden" name="content_type" value="manual">
               <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
               <?php if ($progress_status == 'completed'): ?>
                   <button type="submit" name="action" value="mark_incomplete" class="btn btn-success">
                       <i class="bi bi-check-circle-fill"></i> Marked as Completed (Undo?)
                   </button>
               <?php else: ?>
                   <button type="submit" name="action" value="mark_complete" class="btn btn-primary">
                       <i class="bi bi-check-circle"></i> Mark as Complete
                   </button>
               <?php endif; ?>
           </form>
       </div>
       <?php endif; ?>
        <a href="training.php" class="btn btn-secondary mt-3">Back to Training Modules</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

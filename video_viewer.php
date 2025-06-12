<?php
require_once 'php/config.php';
require_once 'php/session_check.php';
require_once 'php/db_connect.php';
// $current_user_id, $current_username, $current_user_email are available from session_check.php

$manual_id = filter_input(INPUT_GET, 'manual_id', FILTER_VALIDATE_INT);
$videos = [];
$manual_title = ''; // To display context
$error_message = '';
$videos_progress = [];

$progress_feedback_message = $_SESSION['progress_feedback_message'] ?? null;
$progress_feedback_type = $_SESSION['progress_feedback_type'] ?? 'info';
unset($_SESSION['progress_feedback_message'], $_SESSION['progress_feedback_type']);

if (!$manual_id) {
    $error_message = "Invalid manual ID specified for videos.";
} else {
    try {
        // Get manual title for context
        $stmt_manual = $pdo->prepare("SELECT title FROM training_manuals WHERE id = :manual_id");
        $stmt_manual->bindParam(':manual_id', $manual_id, PDO::PARAM_INT);
        $stmt_manual->execute();
        $manual_data = $stmt_manual->fetch();
        if ($manual_data) {
            $manual_title = $manual_data['title'];
        } else {
            $error_message = "Associated manual not found.";
        }

        if (empty($error_message)) { // Proceed only if manual was found
            $stmt = $pdo->prepare("SELECT id, title, video_url, description FROM training_videos WHERE manual_id = :manual_id ORDER BY title");
            $stmt->bindParam(':manual_id', $manual_id, PDO::PARAM_INT);
            $stmt->execute();
            $videos = $stmt->fetchAll();

            if (empty($videos)) {
                if (empty($error_message)) { // Avoid overwriting "Associated manual not found."
                    $error_message = "No videos found for this training module.";
                }
            } else {
                // Fetch progress for all videos listed for this user.
                if (isset($current_user_id)) {
                    $video_ids = array_column($videos, 'id');
                    if (!empty($video_ids)) {
                        try {
                            $sql_progress_videos = "SELECT content_id, status FROM user_progress
                                                    WHERE user_id = :user_id AND content_type = 'video' AND content_id IN (" . implode(',', array_fill(0, count($video_ids), '?')) . ")";
                            $stmt_videos_progress = $pdo->prepare($sql_progress_videos);
                            $params = array_merge([$current_user_id], $video_ids);
                            $stmt_videos_progress->execute($params);
                            while($row = $stmt_videos_progress->fetch()){
                                $videos_progress[$row['content_id']] = $row['status'];
                            }
                        } catch (PDOException $e_progress) { // Different variable for exception
                            error_log("Error fetching videos progress: " . $e_progress->getMessage());
                        }
                    }
                }
            }
        }

    } catch (PDOException $e) {
        error_log("PDOException in video_viewer.php: " . $e->getMessage());
        $error_message = "Error fetching videos. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos <?php echo $manual_title ? 'for ' . htmlspecialchars($manual_title) : ''; ?> - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; }
        .video-container iframe, .video-container object, .video-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    </style>
</head>
<body>
    <header>
        <h1>Video Tutorials <?php echo $manual_title ? 'for ' . htmlspecialchars($manual_title) : ''; ?></h1>
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
        <?php if ($error_message && empty($videos)): // Show main error if no videos and error exists ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (!empty($videos)): ?>
            <?php foreach ($videos as $video): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars($video['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (preg_match('/(youtube\.com|youtu\.be)\/(watch\?v=)?([a-zA-Z0-9_-]+)/', $video['video_url'], $matches)):
                            $youtube_id = $matches[3]; ?>
                            <div class="video-container mb-2">
                                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        <?php elseif (filter_var($video['video_url'], FILTER_VALIDATE_URL)): ?>
                            <p><a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" class="btn btn-primary">Watch Video</a> (External Link)</p>
                        <?php else: ?>
                            <p>Video format not recognized or link invalid.</p>
                        <?php endif; ?>
                        <?php if (!empty($video['description'])): ?>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <?php endif; ?>
                        <div class="mt-2">
                           <form action="php/mark_progress.php" method="post" style="display: inline;">
                               <input type="hidden" name="content_id" value="<?php echo $video['id']; ?>">
                               <input type="hidden" name="content_type" value="video">
                               <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                               <?php $video_progress_status = $videos_progress[$video['id']] ?? null; ?>
                               <?php if ($video_progress_status == 'completed'): ?>
                                   <button type="submit" name="action" value="mark_incomplete" class="btn btn-success btn-sm">
                                       <i class="bi bi-check-circle-fill"></i> Completed (Undo?)
                                   </button>
                               <?php else: ?>
                                   <button type="submit" name="action" value="mark_complete" class="btn btn-primary btn-sm">
                                       <i class="bi bi-check-circle"></i> Mark as Complete
                                   </button>
                               <?php endif; ?>
                           </form>
                       </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (!$error_message): // No videos, no error message (should be caught by $error_message above) ?>
             <div class="alert alert-info">No videos available for this module.</div>
        <?php endif; ?>
        <hr>
        <a href="training.php" class="btn btn-secondary mt-3">Back to Training Modules</a>
        <?php if ($manual_id): ?>
             <a href="manual_viewer.php?id=<?php echo $manual_id; ?>" class="btn btn-info mt-3">View Associated Manual</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
            }
        }

    } catch (PDOException $e) {
        error_log("PDOException in video_viewer.php: " . $e->getMessage());
        $error_message = "Error fetching videos. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos <?php echo $manual_title ? 'for ' . htmlspecialchars($manual_title) : ''; ?> - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; }
        .video-container iframe, .video-container object, .video-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    </style>
</head>
<body>
    <header>
        <h1>Video Tutorials <?php echo $manual_title ? 'for ' . htmlspecialchars($manual_title) : ''; ?></h1>
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
        <?php if ($error_message && empty($videos)): // Show main error if no videos and error exists ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error_message); ?></div>
        <?php elseif (!empty($videos)): ?>
            <?php foreach ($videos as $video): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars($video['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (preg_match('/(youtube\.com|youtu\.be)\/(watch\?v=)?([a-zA-Z0-9_-]+)/', $video['video_url'], $matches)):
                            $youtube_id = $matches[3]; ?>
                            <div class="video-container mb-2">
                                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        <?php elseif (filter_var($video['video_url'], FILTER_VALIDATE_URL)): ?>
                            <p><a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" class="btn btn-primary">Watch Video</a> (External Link)</p>
                        <?php else: ?>
                            <p>Video format not recognized or link invalid.</p>
                        <?php endif; ?>
                        <?php if (!empty($video['description'])): ?>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (!$error_message): // No videos, no error message (should be caught by $error_message above) ?>
             <div class="alert alert-info">No videos available for this module.</div>
        <?php endif; ?>
        <hr>
        <a href="training.php" class="btn btn-secondary mt-3">Back to Training Modules</a>
        <?php if ($manual_id): ?>
             <a href="manual_viewer.php?id=<?php echo $manual_id; ?>" class="btn btn-info mt-3">View Associated Manual</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

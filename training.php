<?php
require_once 'php/config.php'; // Adjust path if necessary

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html"); // Adjust path if necessary
    exit;
}
$username = $_SESSION['username'];

// Simulated training data (replace with database later)
$all_training_modules = [
    "Dell XPS 13" => [
        "manual_url" => "manual_viewer.php?model=Dell_XPS_13&type=manual",
        "video_url" => "video_viewer.php?model=Dell_XPS_13&type=video",
        "quiz_url" => "quiz.php?model=Dell_XPS_13",
        "description" => "Comprehensive guide for Dell XPS 13 (Model 9300, 9310) repairs, including screen and battery replacement.",
        "tags" => "Dell, XPS 13, 9300, 9310, ultrabook"
    ],
    "HP Spectre x360" => [
        "manual_url" => "manual_viewer.php?model=HP_Spectre_x360&type=manual",
        "video_url" => "video_viewer.php?model=HP_Spectre_x360&type=video",
        "quiz_url" => "quiz.php?model=HP_Spectre_x360",
        "description" => "Detailed repair instructions for HP Spectre x360 (13-inch, 15-inch models) focusing on keyboard and hinge issues.",
        "tags" => "HP, Spectre, x360, convertible, 2-in-1"
    ],
    "Lenovo ThinkPad T480" => [
        "manual_url" => "manual_viewer.php?model=Lenovo_ThinkPad_T480&type=manual",
        "video_url" => "video_viewer.php?model=Lenovo_ThinkPad_T480&type=video",
        "quiz_url" => "quiz.php?model=Lenovo_ThinkPad_T480",
        "description" => "Step-by-step repair videos and manuals for Lenovo ThinkPad T480, covering RAM and SSD upgrades.",
        "tags" => "Lenovo, ThinkPad, T480, business, laptop"
    ],
    "Apple MacBook Pro 16" => [
        "manual_url" => "manual_viewer.php?model=Apple_MacBook_Pro_16&type=manual",
        "video_url" => "video_viewer.php?model=Apple_MacBook_Pro_16&type=video",
        "quiz_url" => "quiz.php?model=Apple_MacBook_Pro_16",
        "description" => "Repair guides for Apple MacBook Pro 16-inch, including battery and keyboard service.",
        "tags" => "Apple, MacBook Pro, 16-inch, retina"
    ]
];

$search_query = $_GET['search'] ?? '';
$displayed_modules = $all_training_modules;

if (!empty($search_query)) {
    $displayed_modules = array_filter($all_training_modules, function($details, $model_name) use ($search_query) {
        $search_query_lower = strtolower($search_query);
        // Check against model name (key), description, and tags
        if (stripos(strtolower($model_name), $search_query_lower) !== false) {
            return true;
        }
        if (stripos(strtolower($details['description']), $search_query_lower) !== false) {
            return true;
        }
        if (isset($details['tags']) && stripos(strtolower($details['tags']), $search_query_lower) !== false) {
            return true;
        }
        return false;
    }, ARRAY_FILTER_USE_BOTH);
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
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>! Select or Search for a Training Module.</h2>

        <form action="training.php" method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by brand, model, configuration (e.g., Dell, XPS 13, RAM upgrade)" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <?php if (empty($displayed_modules) && !empty($search_query)): ?>
            <div class="alert alert-warning" role="alert">
                No training modules found matching your search: "<?php echo htmlspecialchars($search_query); ?>". Try a different term.
            </div>
        <?php elseif (empty($displayed_modules)): ?>
            <div class="alert alert-info" role="alert">
                No training modules available at the moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($displayed_modules as $model => $details): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($model); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($details['description']); ?></p>
                            <a href="<?php echo htmlspecialchars($details['manual_url']); ?>" class="btn btn-info btn-sm mb-1">View Manual</a>
                            <a href="<?php echo htmlspecialchars($details['video_url']); ?>" class="btn btn-info btn-sm mb-1">View Video Tutorial</a>
                            <a href="<?php echo htmlspecialchars($details['quiz_url']); ?>" class="btn btn-warning btn-sm mb-1">Take Quiz</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

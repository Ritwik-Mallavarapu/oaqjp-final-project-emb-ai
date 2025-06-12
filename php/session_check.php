<?php
// php/session_check.php
// This script should be included at the top of every authenticated page,
// AFTER php/config.php (which calls session_start()).

// Ensure config is loaded if not already (e.g. if this script is included directly sometimes)
if (!defined('SESSION_TIMEOUT_DURATION_SECONDS')) {
    require_once __DIR__ . '/config.php';
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // If not logged in, redirect to login page.
    // Add a message if desired, e.g., ?error=notloggedin
    header("Location: index.html?status=not_logged_in");
    exit;
}

// Check for inactivity timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_DURATION_SECONDS) {
    // Last activity was too long ago, destroy session and redirect
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage

    header("Location: index.html?status=inactive_logout"); // Redirect to login page with message
    exit;
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp for the current request

// Periodically regenerate session ID for active sessions to prevent session fixation
if (isset($_SESSION['session_created_time']) && (time() - $_SESSION['session_created_time']) > SESSION_REGENERATE_ID_INTERVAL_SECONDS) {
    session_regenerate_id(true);
    $_SESSION['session_created_time'] = time(); // Reset creation time after regeneration
}

// Make user details readily available for protected pages if needed (optional convenience)
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_user_email = $_SESSION['email'];

?>

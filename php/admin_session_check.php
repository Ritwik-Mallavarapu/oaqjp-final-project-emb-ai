<?php
// php/admin_session_check.php
// This script should be included at the top of every admin-only page.

// Ensure general session configuration and validation is done first.
// config.php typically calls session_start().
// session_check.php handles general login status and activity timeouts.
if (!defined('SESSION_TIMEOUT_DURATION_SECONDS')) { // Check if config constants are loaded
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/session_check.php'; // This script already handles redirect if not logged in at all.

// Now, perform the role-specific check for 'owner'.
// $current_user_id, $current_username, $current_user_email are available from session_check.php if logged in.

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    // If the user is logged in but not an owner, redirect to their user dashboard.
    // If session_check.php already redirected for not being logged in, this won't be reached.
    // However, if session_check.php allows a non-owner to pass (because they are logged in),
    // this is the crucial owner check.

    $_SESSION['access_denied_message'] = "You do not have permission to access the admin area.";

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        // Logged in, but not an owner. Send to user dashboard.
        header("Location: ../dashboard.php?status=admin_access_denied");
    } else {
        // Should have been caught by session_check.php, but as a fallback.
        header("Location: ../index.html?status=admin_access_denied");
    }
    exit;
}

// If execution reaches here, the user is logged in and is an 'owner'.
// Variables like $current_user_id, $current_username, $current_user_email are available.
?>

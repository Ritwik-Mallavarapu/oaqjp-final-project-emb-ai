<?php
// php/config.php
session_start();

// Automatic logout settings
define('SESSION_TIMEOUT_DURATION_SECONDS', 1800); // 30 minutes (1800 seconds)
define('SESSION_REGENERATE_ID_INTERVAL_SECONDS', 300); // 5 minutes (300 seconds) for session ID regeneration during activity

// Note: The actual session lifetime on the server (session.gc_maxlifetime in php.ini)
// should be at least as long as SESSION_TIMEOUT_DURATION_SECONDS.
// This script handles application-level timeout.
?>

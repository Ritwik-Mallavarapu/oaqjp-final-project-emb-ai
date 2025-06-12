<?php
session_start();

// Temporary user store (replace with database later)
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}
?>

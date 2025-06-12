<?php
require_once 'config.php'; // For session_start()
require_once 'db_connect.php'; // For $pdo database connection

$message = '';
$login_success = false;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // If already logged in, redirect to dashboard
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id']; // Store user ID
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['last_activity'] = time(); // Timestamp of last activity
                $_SESSION['session_created_time'] = time(); // Timestamp for periodic regeneration

                $login_success = true;
                // Regenerate session ID for security
                session_regenerate_id(true); // Initial regeneration for security

                header("Location: ../dashboard.php"); // Redirect to dashboard
                exit;
            } else {
                $message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $message = "Database error during login. Please try again later.";
            error_log("PDOException in login.php: " . $e->getMessage());
        }
    }
}

// If login fails or not a POST request, display the login page (index.html) or this status page.
// For this task, we'll show a status page if there's a message (i.e., login attempt failed).
// If it's a GET request without a prior failed attempt, the user should be on index.html.
// If $message is set, it means a POST attempt failed.
if (!empty($message) && !$login_success) {
    // Instead of showing a separate HTML page:
    header("Location: ../index.html?status=login_failed&error=" . urlencode($message)); // Pass generic status
    exit;
} elseif (!$login_success && $_SERVER["REQUEST_METHOD"] !== "POST") {
    // If it's a GET request to login.php directly without being logged in, redirect to index.html
    header("Location: ../index.html");
    exit;
}
?>

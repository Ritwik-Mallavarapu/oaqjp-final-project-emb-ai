<?php
// php/login.php
require_once 'config.php'; // For session_start()
require_once 'db_connect.php'; // For $pdo database connection

$message = ''; // For potential error messages passed to index.html via GET
$login_success = false;

// If already logged in, redirect based on role
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') {
        header("Location: ../admin_dashboard.php"); // Path to owner dashboard
    } else {
        header("Location: ../dashboard.php"); // Path to user dashboard
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } else {
        try {
            // Fetch role along with other details
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role']; // Store user role in session
                $_SESSION['last_activity'] = time();
                $_SESSION['session_created_time'] = time();

                session_regenerate_id(true);

                $login_success = true;

                // Redirect based on role
                if ($user['role'] === 'owner') {
                    header("Location: ../admin_dashboard.php");
                } else {
                    header("Location: ../dashboard.php");
                }
                exit;
            } else {
                $message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $message = "Database error during login. Please try again later.";
            error_log("PDOException in login.php: " . $e->getMessage());
        }
    }

    // If login failed, redirect back to index.html with an error status and message
    if (!$login_success && !empty($message)) {
        header("Location: ../index.html?status=login_failed&error=" . urlencode($message));
        exit;
    }

} elseif (isset($_GET['status']) && $_GET['status'] === 'login_required') {
    // This case is if another page explicitly redirects here needing login.
    // We can let it fall through or show index.html, but index.html handles most status messages.
    // For now, let index.html's JS handle messages.
    // If no specific message for login_required is on index.html, it will just show the form.
    header("Location: ../index.html?status=login_required");
    exit;
} else {
    // If it's a GET request to login.php directly without being logged in or specific status,
    // redirect to index.html.
    // This also handles cases where form submission is not POST.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: ../index.html");
        exit;
    }
}
?>

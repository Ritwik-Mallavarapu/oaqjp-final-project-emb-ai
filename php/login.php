<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } elseif (!isset($_SESSION['users'][$username])) {
        $message = "Username not found.";
    } else {
        // Get user data
        $user_data = $_SESSION['users'][$username];
        if (password_verify($password, $user_data['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $user_data['email']; // Store email in session too
            header("Location: ../dashboard.php"); // Redirect to dashboard
            exit;
        } else {
            $message = "Incorrect password.";
        }
    }
}
// If login fails or not POST, show simple status page (or redirect to index.html with error)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Status - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header>
        <h1>Login Status</h1>
    </header>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>
        <a href="../index.html" class="btn btn-primary">Back to Login</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

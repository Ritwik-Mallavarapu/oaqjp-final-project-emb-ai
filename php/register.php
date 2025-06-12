<?php
require_once 'config.php';
require_once 'email_functions.php'; // Include the email functions

$message = '';
$registration_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "A valid email address is required.";
    } elseif (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } elseif (isset($_SESSION['users'][$username])) {
        $message = "Username already exists.";
    } else {
        // Check if email already exists (in our temporary session store)
        $email_exists = false;
        foreach($_SESSION['users'] as $user_data) {
            if (isset($user_data['email']) && $user_data['email'] === $email) {
                $email_exists = true;
                break;
            }
        }

        if ($email_exists) {
            $message = "This email address is already registered.";
        } else {
            // Store hashed password and email
            $_SESSION['users'][$username] = [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email
            ];
            $registration_success = true;
            $message = "Registration successful! You can now login.";

            // Send confirmation email
            $email_subject = "Welcome to Dad and Dude Repair!";
            $email_body = "Hello " . htmlspecialchars($username) . ",

Thank you for registering at Dad and Dude Repair. We're excited to have you.

You can now log in and access our training modules and AI repair assistance tools.";

            if (send_email($email, $email_subject, $email_body)) {
                $message .= " A confirmation email has been sent to " . htmlspecialchars($email) . ".";
            } else {
                $message .= " However, we couldn't send a confirmation email at this time. Please contact support if you don't receive it shortly.";
                // Log this issue server-side if possible
                error_log("Failed to send registration email to: $email for user: $username");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Status - Dad and Dude Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header>
        <h1>Registration Status</h1>
    </header>
    <div class="container">
        <p class="<?php echo $registration_success ? 'alert alert-success' : 'alert alert-danger'; ?>"><?php echo $message; ?></p>
        <?php if ($registration_success): ?>
            <a href="../index.html" class="btn btn-primary">Go to Login</a>
        <?php else: ?>
            <a href="../register.html" class="btn btn-secondary">Back to Registration</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

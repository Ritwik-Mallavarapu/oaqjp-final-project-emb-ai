<?php
require_once 'config.php'; // For session_start()
require_once 'db_connect.php'; // For $pdo database connection
require_once 'email_functions.php'; // Include the NEW email functions for PHPMailer

$message = '';
$registration_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "A valid email address is required.";
    } elseif (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->fetch()) {
                $message = "Username or email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
                $insert_stmt->bindParam(':username', $username);
                $insert_stmt->bindParam(':email', $email);
                $insert_stmt->bindParam(':password_hash', $password_hash);

                if ($insert_stmt->execute()) {
                    $registration_success = true;
                    $message = "Registration successful! You can now login.";
                    // $user_id = $pdo->lastInsertId();

                    // Send confirmation email using PHPMailer
                    $email_subject = "Welcome to Dad and Dude Repair!";
                    $email_html_body = "<h1>Welcome, " . htmlspecialchars($username) . "!</h1>";
                    $email_html_body .= "<p>Thank you for registering at Dad and Dude Repair. We're excited to have you.</p>";
                    $email_html_body .= "<p>You can now log in and access our training modules and AI repair assistance tools.</p>";
                    $email_html_body .= "<p>If you have any questions, feel free to contact our support team.</p>";
                    $email_html_body .= "<p>Best regards,<br>The Dad and Dude Repair Team</p>";

                    // Plain text version
                    $email_text_body = "Welcome, " . htmlspecialchars($username) . "!

";
                    $email_text_body .= "Thank you for registering at Dad and Dude Repair. We're excited to have you.

";
                    $email_text_body .= "You can now log in and access our training modules and AI repair assistance tools.

";
                    $email_text_body .= "If you have any questions, feel free to contact our support team.

";
                    $email_text_body .= "Best regards,
The Dad and Dude Repair Team";

                    if (send_email_phpmailer($email, $username, $email_subject, $email_html_body, $email_text_body)) {
                        $message .= " A confirmation email has been sent to " . htmlspecialchars($email) . ".";
                    } else {
                        $message .= " However, we couldn't send a confirmation email at this time. Please check your email or contact support.";
                        // The error is already logged by send_email_phpmailer function
                    }

                } else {
                    $message = "Registration failed. Please try again.";
                    error_log("Registration failed during DB insert for username: $username");
                }
            }
        } catch (PDOException $e) {
            $message = "Database error during registration. Please try again later.";
            error_log("PDOException in register.php: " . $e->getMessage());
        }
    }
}
// The rest of register.php (HTML part) remains the same
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
    <div class="container mt-4">
        <div class="alert <?php echo $registration_success ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php if ($registration_success): ?>
            <a href="../index.html" class="btn btn-primary">Go to Login</a>
        <?php else: ?>
            <a href="../register.html" class="btn btn-secondary">Back to Registration</a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

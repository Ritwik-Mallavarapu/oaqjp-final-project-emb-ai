<?php
// Basic email sending function
// IMPORTANT: This relies on the server being configured to send emails via PHP's mail().
// This might not work on all local development setups without additional configuration
// (e.g., configuring sendmail or using an SMTP library like PHPMailer).

function send_email($to, $subject, $message_body) {
    $headers = "From: no-reply@dadanDude.com
"; // Replace with a valid sender if needed
    $headers .= "Reply-To: no-reply@dadanDude.com
";
    $headers .= "Content-Type: text/html; charset=UTF-8
";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Basic HTML template for the email
    $html_message = "<html><head><title>" . htmlspecialchars($subject) . "</title></head><body>";
    $html_message .= "<p>" . nl2br(htmlspecialchars($message_body)) . "</p>";
    $html_message .= "<p>Thank you,<br>Dad and Dude Repair Team</p>";
    $html_message .= "</body></html>";

    if (mail($to, $subject, $html_message, $headers)) {
        return true;
    } else {
        // Log error or handle silently - for now, returning false
        error_log("Email sending failed to: $to, Subject: $subject");
        return false;
    }
}
?>

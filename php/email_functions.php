<?php
// php/email_functions.php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer's autoloader or include files manually
// Assuming files are in php/lib/PHPMailer/
require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

function send_email_phpmailer($to, $to_name, $subject, $html_body, $text_body = '') {
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings - REPLACE WITH YOUR ACTUAL SMTP CREDENTIALS AND SETTINGS
        // It's strongly recommended to use environment variables or a secure config file for these.
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Change to SMTP::DEBUG_SERVER for detailed debugging output
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Your SMTP server (e.g., smtp.gmail.com for Gmail)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@example.com'; // Your SMTP username
        $mail->Password   = 'your_smtp_password'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Or PHPMailer::ENCRYPTION_SMTPS
        $mail->Port       = 587; // For TLS; use 465 for SMTPS

        // Recipients
        $mail->setFrom('no-reply@dadanDudeRepair.com', 'Dad and Dude Repair'); // Sender's email and name
        $mail->addAddress($to, $to_name); // Add a recipient
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = $text_body ?: strip_tags($html_body); // Plain text version

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error or handle it appropriately
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<?php
// php/db_connect.php

require_once 'db_config.php'; // Include the database credentials

// PDO Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation of prepared statements for security
];

try {
    // Create a new PDO instance (database connection)
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Handle connection error
    // In a production environment, you might log this error and show a generic message to the user.
    // For development, it's useful to see the error.
    error_log("Database Connection Error: " . $e->getMessage());
    // You could throw the exception again or die with a message.
    // For now, let's ensure the script stops and shows an error if connection fails.
    die("Database connection failed. Please check server logs or contact support. Error: " . $e->getMessage());
    // A more user-friendly approach for production would be:
    // die("Sorry, we are experiencing technical difficulties. Please try again later.");
}

// The $pdo object is now available for use in any script that includes db_connect.php
// For example, to use it in another script:
// require_once 'php/db_connect.php'; // $pdo is now available
// $stmt = $pdo->query("SELECT * FROM users");
// $users = $stmt->fetchAll();
?>

<?php
// php/handle_add_manual.php
require_once __DIR__ . '/admin_session_check.php';
// $current_user_id, $current_username, $current_user_email are available and owner access confirmed.
// CSRF Check (still placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken($_POST['csrf_token']) */ ) {
//    $_SESSION['manual_op_success_message'] = "Invalid request (CSRF token mismatch).";
//    header("Location: ../admin_manage_manuals.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $config_details = trim($_POST['configuration_details'] ?? null);
    $description = trim($_POST['description'] ?? null);

    // Store form data in session in case of error and redirect
    $_SESSION['manual_form_data'] = $_POST;

    // Basic validation
    if (empty($brand) || empty($model) || empty($title)) {
        $_SESSION['manual_form_error'] = "Brand, Model, and Title are required fields.";
        header("Location: ../admin_add_manual.php");
        exit;
    }

    if (isset($_FILES['manual_pdf']) && $_FILES['manual_pdf']['error'] == UPLOAD_ERR_OK) {
        $pdf_file = $_FILES['manual_pdf'];
        $file_name = $pdf_file['name'];
        $file_tmp_name = $pdf_file['tmp_name'];
        $file_size = $pdf_file['size'];
        $file_type = $pdf_file['type'];
        $file_ext_parts = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext_parts));

        $allowed_ext = 'pdf';
        $max_file_size = 10 * 1024 * 1024; // 10MB

        if ($file_ext !== $allowed_ext) {
            $_SESSION['manual_form_error'] = "Invalid file type. Only PDF files are allowed.";
            header("Location: ../admin_add_manual.php");
            exit;
        }
        if ($file_size > $max_file_size) {
            $_SESSION['manual_form_error'] = "File is too large. Maximum size is 10MB.";
            header("Location: ../admin_add_manual.php");
            exit;
        }

        // Sanitize filename and create a unique name to prevent overwrites/issues
        $safe_brand = preg_replace("/[^a-zA-Z0-9_-]/", "_", $brand);
        $safe_model = preg_replace("/[^a-zA-Z0-9_-]/", "_", $model);
        $new_filename = $safe_brand . '_' . $safe_model . '_' . time() . '.' . $file_ext;

        $upload_dir = '../uploads/manuals_pdf/'; // Relative to this script in php/
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) { // Check again after mkdir attempt
             $_SESSION['manual_form_error'] = "Failed to create upload directory. Check server permissions for 'uploads/manuals_pdf/'. Path: " . realpath($upload_dir);
            header("Location: ../admin_add_manual.php");
            exit;
        }
        $destination_path = $upload_dir . $new_filename;
        $db_file_path = 'uploads/manuals_pdf/' . $new_filename; // Path to store in DB (relative to project root)


        if (move_uploaded_file($file_tmp_name, $destination_path)) {
            try {
                $sql = "INSERT INTO training_manuals (brand, model, title, configuration_details, description, file_path, created_at, updated_at)
                        VALUES (:brand, :model, :title, :config_details, :description, :file_path, NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':brand' => $brand,
                    ':model' => $model,
                    ':title' => $title,
                    ':config_details' => $config_details,
                    ':description' => $description,
                    ':file_path' => $db_file_path
                ]);

                $_SESSION['manual_op_success_message'] = "Training manual '".htmlspecialchars($title)."' added successfully!";
                unset($_SESSION['manual_form_data']); // Clear form data on success
                header("Location: ../admin_manage_manuals.php");
                exit;

            } catch (PDOException $e) {
                error_log("PDOException in handle_add_manual.php: " . $e->getMessage());
                // Potentially delete uploaded file if DB insert fails
                if (file_exists($destination_path)) unlink($destination_path);
                $_SESSION['manual_form_error'] = "Database error while adding manual. Please try again. " . $e->getMessage();
            }
        } else {
            $_SESSION['manual_form_error'] = "Failed to move uploaded PDF file. Check permissions for 'uploads/manuals_pdf/'. Error code: " . $_FILES['manual_pdf']['error'];
        }
    } elseif ($_FILES['manual_pdf']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['manual_pdf']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['manual_form_error'] = "Error uploading PDF file. Error code: " . $_FILES['manual_pdf']['error'];
    } else {
         $_SESSION['manual_form_error'] = "PDF file is required.";
    }

    header("Location: ../admin_add_manual.php"); // Redirect back to form on error
    exit;

} else {
    // Not a POST request or other issue
    header("Location: ../admin_add_manual.php");
    exit;
}
?>

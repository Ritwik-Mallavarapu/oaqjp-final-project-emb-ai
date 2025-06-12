<?php
// php/handle_edit_manual.php
require_once __DIR__ . '/admin_session_check.php';
// CSRF Check (placeholder)
// if (!isset($_POST['csrf_token']) /* || !verifyCsrfToken... */ ) {
//    $_SESSION['manual_op_success_message'] = "Invalid request (CSRF).";
//    header("Location: ../admin_manage_manuals.php");
//    exit;
// }
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_id'])) {
    $manual_id = filter_input(INPUT_POST, 'manual_id', FILTER_VALIDATE_INT);
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $config_details = trim($_POST['configuration_details'] ?? null);
    $description = trim($_POST['description'] ?? null);
    $existing_pdf_path = trim($_POST['existing_pdf_path'] ?? '');

    // Store form data in session in case of error and redirect
    $_SESSION['manual_form_data'] = $_POST;


    if (!$manual_id) {
        $_SESSION['manual_form_error'] = "Manual ID is missing for update.";
        header("Location: ../admin_edit_manual.php?id=" . ($manual_id ?: '')); // May not have ID if it was missing
        exit;
    }
    if (empty($brand) || empty($model) || empty($title)) {
        $_SESSION['manual_form_error'] = "Brand, Model, and Title are required fields.";
        header("Location: ../admin_edit_manual.php?id=" . $manual_id);
        exit;
    }

    $new_pdf_path_in_db = $existing_pdf_path; // Assume existing path unless new file uploaded
    $old_pdf_to_delete_on_server = null;

    // Check for new PDF upload
    if (isset($_FILES['manual_pdf']) && $_FILES['manual_pdf']['error'] == UPLOAD_ERR_OK) {
        $pdf_file = $_FILES['manual_pdf'];
        $file_name = $pdf_file['name'];
        $file_tmp_name = $pdf_file['tmp_name'];
        $file_size = $pdf_file['size'];
        $file_ext_parts = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext_parts));

        $allowed_ext = 'pdf';
        $max_file_size = 10 * 1024 * 1024; // 10MB

        if ($file_ext !== $allowed_ext) {
            $_SESSION['manual_form_error'] = "Invalid new file type. Only PDF files are allowed.";
            header("Location: ../admin_edit_manual.php?id=" . $manual_id);
            exit;
        }
        if ($file_size > $max_file_size) {
            $_SESSION['manual_form_error'] = "New file is too large. Maximum size is 10MB.";
            header("Location: ../admin_edit_manual.php?id=" . $manual_id);
            exit;
        }

        $safe_brand = preg_replace("/[^a-zA-Z0-9_-]/", "_", $brand);
        $safe_model = preg_replace("/[^a-zA-Z0-9_-]/", "_", $model);
        $new_filename = $safe_brand . '_' . $safe_model . '_' . time() . '.' . $file_ext;

        $upload_dir = '../uploads/manuals_pdf/'; // Relative to this script in php/
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) { // Check again after mkdir
             $_SESSION['manual_form_error'] = "Failed to ensure upload directory exists.";
             header("Location: ../admin_edit_manual.php?id=" . $manual_id);
             exit;
        }
        $destination_path_on_server = $upload_dir . $new_filename;
        $new_pdf_path_in_db = 'uploads/manuals_pdf/' . $new_filename; // Path relative to project root for DB

        if (move_uploaded_file($file_tmp_name, $destination_path_on_server)) {
            // New file uploaded successfully, mark old one for deletion if it exists and is different
            if (!empty($existing_pdf_path) && $existing_pdf_path !== $new_pdf_path_in_db) {
                 $old_pdf_to_delete_on_server = '../' . $existing_pdf_path; // Path relative to this script for deletion
            }
        } else {
            $_SESSION['manual_form_error'] = "Failed to move newly uploaded PDF file. Error Code: " . $_FILES['manual_pdf']['error'];
            header("Location: ../admin_edit_manual.php?id=" . $manual_id);
            exit;
        }
    } elseif (isset($_FILES['manual_pdf']) && $_FILES['manual_pdf']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['manual_pdf']['error'] !== UPLOAD_ERR_OK) {
        // An error occurred with the upload, but it wasn't just "no file submitted"
        $_SESSION['manual_form_error'] = "Error with new PDF file upload. Error code: " . $_FILES['manual_pdf']['error'];
        header("Location: ../admin_edit_manual.php?id=" . $manual_id);
        exit;
    }
    // If no new file was uploaded, $new_pdf_path_in_db remains $existing_pdf_path

    // Update database
    try {
        $sql = "UPDATE training_manuals SET
                brand = :brand,
                model = :model,
                title = :title,
                configuration_details = :config_details,
                description = :description,
                file_path = :file_path,
                updated_at = NOW()
                WHERE id = :manual_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':brand' => $brand,
            ':model' => $model,
            ':title' => $title,
            ':config_details' => $config_details,
            ':description' => $description,
            ':file_path' => $new_pdf_path_in_db,
            ':manual_id' => $manual_id
        ]);

        // If DB update successful and an old PDF was marked for deletion, delete it
        if ($old_pdf_to_delete_on_server && file_exists($old_pdf_to_delete_on_server)) {
            unlink($old_pdf_to_delete_on_server);
        }

        $_SESSION['manual_op_success_message'] = "Training manual '".htmlspecialchars($title)."' updated successfully!";
        unset($_SESSION['manual_form_data']); // Clear form data on success
        header("Location: ../admin_manage_manuals.php");
        exit;

    } catch (PDOException $e) {
        error_log("PDOException in handle_edit_manual.php: " . $e->getMessage());
        // If DB update fails but a new file was uploaded, attempt to delete the newly uploaded file to prevent orphans
        if (isset($destination_path_on_server) && $new_pdf_path_in_db !== $existing_pdf_path && file_exists($destination_path_on_server)) {
            unlink($destination_path_on_server);
        }
        $_SESSION['manual_form_error'] = "Database error while updating manual. " . $e->getMessage();
        header("Location: ../admin_edit_manual.php?id=" . $manual_id);
        exit;
    }
} else {
    $_SESSION['manual_op_success_message'] = "Invalid request for editing manual."; // To admin_manage_manuals
    header("Location: ../admin_manage_manuals.php");
    exit;
}
?>

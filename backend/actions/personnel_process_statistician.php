<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = intval($_POST['submission_id']);
    $action = $_POST['action'] ?? ''; // 'Approve' or 'Reject'

    if ($action === 'Approve' || $action === 'Reject') {
        
        $status = ($action === 'Approve') ? 'Approved' : 'Rejected';
        
        // Check if a file was actually uploaded
        $has_file = isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK;

        if ($has_file) {
            // --- SCENARIO 1: File WAS Attached ---
            $file = $_FILES['result_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['docx', 'pdf', 'txt', 'csv', 'xlsx', 'sav', 'png', 'jpg'];

            if (!in_array($ext, $allowed_exts)) {
                $_SESSION['flash_error'] = "Invalid file type. Allowed: .docx, .pdf, .txt, .csv, .xlsx, .sav, .png, .jpg";
                header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_statistician");
                exit();
            }
            
            // Generate safe unique filename
            $filename = "result_" . time() . "_" . $submission_id . "." . $ext;
            
            // Define target directory
            $targetDir = "../../uploads/statistician_results/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
                // Update Database with Status AND File Path
                $stmt = $conn->prepare("UPDATE statistician SET status = ?, result_file_path = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $filename, $submission_id);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_success'] = "Submission $status and File Uploaded successfully!";
                } else {
                    $_SESSION['flash_error'] = "Database error. Please try again.";
                }
                $stmt->close();
            } else {
                $_SESSION['flash_error'] = "Failed to save the uploaded file.";
            }

        } else {
            // --- SCENARIO 2: No File Attached ---
            // Update Database with Status ONLY
            $stmt = $conn->prepare("UPDATE statistician SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $submission_id);
            
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Submission $status successfully without an attachment!";
            } else {
                $_SESSION['flash_error'] = "Database error. Please try again.";
            }
            $stmt->close();
        }
    }

    header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_statistician");
    exit();
}
?>
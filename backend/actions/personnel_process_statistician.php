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
        
        // Handle File Upload for BOTH Approve and Reject
        if (!isset($_FILES['result_file']) || $_FILES['result_file']['error'] !== UPLOAD_ERR_OK) {
            // Tell them they missed the file for whichever button they clicked
            $_SESSION['flash_error'] = "You must upload a file to " . strtolower($action) . " this submission.";
            header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_statistician");
            exit();
        }

        $file = $_FILES['result_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Define the strictly allowed file extensions
        $allowed_exts = ['docx', 'pdf', 'txt', 'csv', 'xlsx', 'sav', 'png', 'jpg'];

        if (!in_array($ext, $allowed_exts)) {
            $_SESSION['flash_error'] = "Invalid file type. Allowed: .docx, .pdf, .txt, .csv, .xlsx, .sav, .png, .jpg";
            header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_statistician");
            exit();
        }
        
        // Generate safe unique filename
        $filename = "result_" . time() . "_" . $submission_id . "." . $ext;
        
        // Define target directory (create it if it doesn't exist)
        $targetDir = "../../uploads/statistician_results/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            
            // Set the correct status string for the database based on the button clicked
            $status = ($action === 'Approve') ? 'Approved' : 'Rejected';

            // Update Database with the Status AND the File Path for both actions
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
    }

    header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_statistician");
    exit();
}
?>
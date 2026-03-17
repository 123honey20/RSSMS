<?php
session_start();
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Check if a file was uploaded without errors
if (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
    exit;
}

$file = $_FILES['certificate'];

// Validate file type (Allow PNG, JPG, JPEG, and PDF)
$allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'];
if (!in_array($file['type'], $allowedMimeTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload a PNG, JPG, or PDF document.']);
    exit;
}

// Determine the correct extension based on the uploaded file's MIME type
$extension = '';
if ($file['type'] === 'image/png') {
    $extension = 'png';
} elseif ($file['type'] === 'image/jpeg' || $file['type'] === 'image/jpg') {
    $extension = 'jpg';
} elseif ($file['type'] === 'application/pdf') {
    $extension = 'pdf';
}

// Define the directory
$target_dir = "../../frontend/images/certificates/proposal-certificate/";

// Create directory if it doesn't exist just in case
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// in the folder at the same time, we delete any existing files with our naming convention.
$possible_extensions = ['png', 'jpg', 'jpeg', 'pdf'];
foreach ($possible_extensions as $ext) {
    $old_file = $target_dir . "Proposal_Certificate." . $ext;
    if (file_exists($old_file)) {
        unlink($old_file); // Delete the old file
    }
}

// Define the exact path for the NEW file
$target_file = $target_dir . "Proposal_Certificate." . $extension;

// Move the uploaded file
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    echo json_encode(['success' => true, 'message' => 'Certificate template updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
}
?>
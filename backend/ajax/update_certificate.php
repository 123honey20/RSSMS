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

// Validate file type (Only allow PNG, JPG, JPEG)
$allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg'];
if (!in_array($file['type'], $allowedMimeTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file format. Please upload a PNG or JPG image.']);
    exit;
}

// Define the exact path to overwrite your existing blank certificate
$target_dir = "../../frontend/images/certificates/proposal-certificate/";
$target_file = $target_dir . "Proposal_Certificate.png"; // We overwrite the exact file used by the system

// Create directory if it doesn't exist just in case
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// Move the uploaded file and replace the old one
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    echo json_encode(['success' => true, 'message' => 'Certificate template updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
}
?>
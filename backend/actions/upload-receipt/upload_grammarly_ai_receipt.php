<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$user_id = (int)$_SESSION['user'];
$round = isset($_POST['round']) ? (int)$_POST['round'] : 0;

if ($round <= 0) {
    die("Invalid round.");
}

// Get Student Info
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id LIMIT 1");
$student = $res->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

$student_id = (int)$student['id'];

// Verify Transaction Exists & Belongs To Student
$stmt = $conn->prepare("
    SELECT * FROM grammarly_ai_transactions
    WHERE student_id = ?
    AND round = ?
    LIMIT 1
");
$stmt->bind_param("ii", $student_id, $round);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

if (!$transaction) {
    die("Transaction not found.");
}

// Allow Upload Only If Status Is 'No Receipt' OR 'Rejected'
if (!in_array($transaction['status'], ['No Receipt', 'Rejected'])) {
    die("Receipt already uploaded or transaction already processed.");
}

// Validate File Upload
if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
    die("File upload failed.");
}

$file = $_FILES['receipt_file'];

$maxFileSize = 5 * 1024 * 1024; // 5MB

// Check extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'txt'];

if (!in_array($extension, $allowedExtensions)) {
    die("Invalid file type. Allowed: JPG, PNG, PDF, TXT.");
}

// Check MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedTypes = [
    'image/jpeg',
    'image/png',
    'application/pdf',
    'text/plain'
];

if (!in_array($mimeType, $allowedTypes)) {
    die("Invalid file type.");
}

// Check file size
if ($file['size'] > $maxFileSize) {
    die("File too large. Maximum size is 5MB.");
}

// Generate Safe Filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$extension = strtolower($extension);

$newFileName = "receipt_student_{$student_id}_round_{$round}_" . time() . "." . $extension;

// Create Upload Directory If Not Exists
$uploadDir = "../../../uploads/grammarly_ai/receipts/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    die("Failed to save file.");
}

// Update Transaction
$updateStmt = $conn->prepare("
    UPDATE grammarly_ai_transactions
    SET receipt_path = ?, status = 'Receipt Uploaded'
    WHERE id = ?
");

$updateStmt->bind_param("si", $newFileName, $transaction['id']);
$updateStmt->execute();

// Redirect Back
$_SESSION['flash_success'] = "Receipt uploaded successfully for Round {$round}.";

header("Location: ../../../frontend/dashboards/student_dashboard.php?page=transaction_grammarly_ai");
exit();

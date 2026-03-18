<?php
session_start();
require_once "../../config/database.php";

// IMPORTANT: Make sure you have PHPMailer installed (via Composer or manually included)
// If using Composer, uncomment the line below:
// require '../../../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// =========================================================================
// EMAIL NOTIFICATION SYSTEM
// =========================================================================

try {
    // 1. Get Student Details for the Email
    $stmtStudentDetails = $conn->prepare("
        SELECT u.email, u.firstname, u.lastname, s.control_number 
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        WHERE s.id = ?
    ");
    $stmtStudentDetails->bind_param("i", $student_id);
    $stmtStudentDetails->execute();
    $studentData = $stmtStudentDetails->get_result()->fetch_assoc();
    $studentName = $studentData['firstname'] . ' ' . $studentData['lastname'];
    $studentEmail = $studentData['email'];
    $controlNo = $studentData['control_number'];

    // 2. Configure PHPMailer (Replace with your actual SMTP credentials)
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // e.g., smtp.gmail.com
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_email@gmail.com'; // YOUR EMAIL HERE
    $mail->Password   = 'your_app_password';    // YOUR APP PASSWORD HERE
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 465;
    
    $mail->setFrom('your_email@gmail.com', 'Research Support Services');
    $mail->isHTML(true);

    // 3. Send Email to the Student (Confirmation)
    $mail->clearAddresses();
    $mail->addAddress($studentEmail, $studentName);
    $mail->Subject = "Receipt Upload Successful - Grammarly & AI Checking (Round {$round})";
    
    $studentMsg = "
        <div style='font-family: Arial, sans-serif; color: #333; max-w: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
            <h2 style='color: #1e3a8a;'>Receipt Upload Confirmation</h2>
            <p>Hello <strong>{$studentName}</strong>,</p>
            <p>Your payment receipt for <strong>Grammarly & AI Checking (Round {$round})</strong> has been successfully uploaded.</p>
            <p><strong>Control Number:</strong> {$controlNo}</p>
            <p>The assigned personnel will review your receipt shortly. You will receive another email once it has been approved or rejected.</p>
            <br>
            <p style='font-size: 12px; color: #6b7280;'>This is an automated message. Please do not reply.</p>
        </div>
    ";
    $mail->Body = $studentMsg;
    $mail->send();

    // 4. Send Email to ALL Grammarly & AI Checking Personnel (Notification)
    $stmtPersonnel = $conn->prepare("
        SELECT u.email, p.full_name 
        FROM users u 
        JOIN personnel p ON u.id = p.user_id 
        WHERE p.service_role = 'Grammarly & AI Checking'
    ");
    $stmtPersonnel->execute();
    $personnelRes = $stmtPersonnel->get_result();

    if ($personnelRes->num_rows > 0) {
        $mail->clearAddresses(); // Clear student address
        
        while ($personnel = $personnelRes->fetch_assoc()) {
            $mail->addAddress($personnel['email'], $personnel['full_name']);
        }

        $mail->Subject = "New Receipt Uploaded - Control No: {$controlNo}";
        
        $personnelMsg = "
            <div style='font-family: Arial, sans-serif; color: #333; max-w: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                <h2 style='color: #d97706;'>Action Required: New Receipt</h2>
                <p>A student has uploaded a payment receipt for Grammarly & AI Checking.</p>
                <ul>
                    <li><strong>Student Name:</strong> {$studentName}</li>
                    <li><strong>Control Number:</strong> {$controlNo}</li>
                    <li><strong>Round:</strong> {$round}</li>
                </ul>
                <p>Please log in to the personnel dashboard to review and approve/reject this receipt.</p>
                <br>
                <p style='font-size: 12px; color: #6b7280;'>This is an automated system notification.</p>
            </div>
        ";
        $mail->Body = $personnelMsg;
        $mail->send();
    }

} catch (Exception $e) {
    // If email fails, we don't want to break the upload process completely.
    // The receipt is already in the database, so we just log the error or ignore it.
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

// =========================================================================

// Redirect Back
$_SESSION['flash_success'] = "Receipt uploaded successfully for Round {$round}.";
header("Location: ../../../frontend/dashboards/student_dashboard.php?page=student_transaction_grammarly_ai");
exit();
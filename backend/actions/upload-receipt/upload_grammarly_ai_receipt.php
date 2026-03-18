<?php
session_start();
require_once "../../config/database.php";
require '../../../vendor/autoload.php'; 

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

// Verify Transaction
$stmt = $conn->prepare("SELECT * FROM grammarly_ai_transactions WHERE student_id = ? AND round = ? LIMIT 1");
$stmt->bind_param("ii", $student_id, $round);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction || !in_array($transaction['status'], ['No Receipt', 'Rejected'])) {
    die("Invalid transaction status.");
}

if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
    die("File upload failed.");
}

$file = $_FILES['receipt_file'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$newFileName = "receipt_student_{$student_id}_round_{$round}_" . time() . "." . $extension;
$uploadDir = "../../../uploads/grammarly_ai/receipts/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
    die("Failed to save file.");
}

// Update Transaction
$updateStmt = $conn->prepare("UPDATE grammarly_ai_transactions SET receipt_path = ?, status = 'Receipt Uploaded' WHERE id = ?");
$updateStmt->bind_param("si", $newFileName, $transaction['id']);
$updateStmt->execute();

// =========================================================================
// MODERN EMAIL NOTIFICATION SYSTEM
// =========================================================================

try {
    $stmtData = $conn->prepare("SELECT u.email, s.control_number, s.research_leader FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?");
    $stmtData->bind_param("i", $student_id);
    $stmtData->execute();
    $studentData = $stmtData->get_result()->fetch_assoc();
    
    $studentName = $studentData['research_leader']; 
    $studentEmail = $studentData['email'];
    $controlNo = $studentData['control_number'];

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'joshuaalmodiel119@gmail.com';
    $mail->Password   = 'nprf grsd yrxt auyz'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Support');
    $mail->isHTML(true);

    // --- SHARED MODERN CSS ---
    $emailHeader = "
        <div style='background-color: #f8fafc; padding: 20px; font-family: sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>";
    $emailFooter = "
                <div style='background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b;'>
                    <p style='margin: 0;'>This is an automated notification from the Research Support Services Management System.</p>
                    <p style='margin: 5px 0 0 0;'>&copy; 2026 RSSMS. All rights reserved.</p>
                </div>
            </div>
        </div>";

    // 1. EMAIL TO STUDENT (BLUE THEME)
    $mail->addAddress($studentEmail, $studentName);
    $mail->Subject = "Receipt Uploaded Successfully (Round {$round})";
    $mail->Body = $emailHeader . "
        <div style='background: #2563eb; padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Receipt Received</h1>
        </div>
        <div style='padding: 30px; line-height: 1.6; color: #334155;'>
            <p style='font-size: 18px;'>Hello <strong>{$studentName}</strong>,</p>
            <p>Your payment receipt for <strong>Grammarly & AI Checking</strong> has been uploaded successfully for processing.</p>
            <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <p style='margin: 0;'><strong>Control Number:</strong> <span style='color: #2563eb;'>{$controlNo}</span></p>
                <p style='margin: 5px 0 0 0;'><strong>Submission Round:</strong> Round {$round}</p>
            </div>
            <p>Our personnel will review your receipt shortly. You will be notified via email once your payment is verified so you can proceed with your document upload.</p>
            <a href='' style='display: inline-block; background: #2563eb; color: #ffffff; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px;'>Check Dashboard Status</a>
        </div>" . $emailFooter;
    $mail->send();

    // 2. EMAIL TO PERSONNEL (ORANGE THEME)
    $stmtPersonnel = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.service_role = 'Grammarly & AI Checking'");
    $stmtPersonnel->execute();
    $personnelRes = $stmtPersonnel->get_result();

    if ($personnelRes->num_rows > 0) {
        $mail->clearAddresses(); 
        while ($pRow = $personnelRes->fetch_assoc()) {
            $mail->addAddress($pRow['email'], $pRow['full_name']);
        }
        $mail->Subject = "ACTION REQUIRED: New Receipt (Control No: {$controlNo})";
        $mail->Body = $emailHeader . "
            <div style='background: #ea580c; padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>New Receipt for Review</h1>
            </div>
            <div style='padding: 30px; line-height: 1.6; color: #334155;'>
                <p style='font-size: 18px;'>Dear Personnel,</p>
                <p>A new payment receipt has been submitted and is waiting for your verification.</p>
                <div style='background: #fff7ed; border: 1px solid #ffedd5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Student:</strong> {$studentName}</p>
                    <p style='margin: 5px 0;'><strong>Control No:</strong> {$controlNo}</p>
                    <p style='margin: 5px 0 0 0;'><strong>Service:</strong> Grammarly & AI Checking (Round {$round})</p>
                </div>
                <p>Please log in to the system to approve or reject this transaction.</p>
                <a href='' style='display: inline-block; background: #ea580c; color: #ffffff; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px;'>Review Receipt Now</a>
            </div>" . $emailFooter;
        $mail->send();
    }

} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

$_SESSION['flash_success'] = "Receipt uploaded successfully for Round {$round}.";
header("Location: ../../../frontend/dashboards/student_dashboard.php?page=student_transaction_grammarly_ai");
exit();
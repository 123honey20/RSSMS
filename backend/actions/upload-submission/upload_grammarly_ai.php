<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user'];
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_grammarly_ai";
$success_url = "../../../frontend/dashboards/student_dashboard.php?page=students_rs_grammarly_ai";

function redirectWithError(string $message, string $url) {
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// 1. Get student details
$stmt = $conn->prepare("
    SELECT s.id, s.control_number, s.research_leader, s.thesis_title, u.school_id, u.email 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$student_id = $studentData['id'];
$school_id = $studentData['school_id'];
$studentEmail = $studentData['email'];
$studentName = $studentData['research_leader'];
$controlNo = $studentData['control_number'];
$thesisTitle = $studentData['thesis_title'];
$stmt->close();

// --- NEW: FETCH THE *CURRENT* ASSIGNED PERSONNEL FROM ADMIN'S DASHBOARD ---
$assignStmt = $conn->prepare("SELECT assigned_personnel_id FROM service_applications WHERE student_id = ? AND service_type = 'Grammarly & AI Checking' AND status = 'Approved'");
$assignStmt->bind_param("i", $student_id);
$assignStmt->execute();
$assignRes = $assignStmt->get_result()->fetch_assoc();
$active_personnel_id = $assignRes['assigned_personnel_id'] ?? null;
$assignStmt->close();

$update_mode = $_POST['update_mode'] ?? 'both';
$round = intval($_POST['round'] ?? 1);

$doReceipt = in_array($update_mode, ['both', 'receipt']);
$doDocument = in_array($update_mode, ['both', 'document']);

$receiptExt = '';
$docExt = '';

// 2. Validate Files Early
if ($doReceipt) {
    if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
        redirectWithError("Please select a valid payment receipt to upload.", $redirect_url);
    }
    $receiptExt = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($receiptExt, ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])) {
        redirectWithError("Invalid receipt file extension. Allowed: JPG, PNG, PDF, DOC, DOCX", $redirect_url);
    }
}

if ($doDocument) {
    if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        redirectWithError("Please select a valid document file to upload.", $redirect_url);
    }
    $docExt = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($docExt, ['pdf', 'docx', 'doc', 'odt', 'rtf', 'txt', 'pptx'])) {
        redirectWithError("Invalid document file extension.", $redirect_url);
    }
}

// Fetch check document locks
$checkSub = $conn->prepare("SELECT id, file_path, is_locked FROM grammarly_ai WHERE student_id = ? AND round = ? LIMIT 1");
$checkSub->bind_param("ii", $student_id, $round);
$checkSub->execute();
$existingDoc = $checkSub->get_result()->fetch_assoc();
$checkSub->close();

// ENFORCE SECURITY LOCK!
if ($doDocument && $existingDoc && (int)$existingDoc['is_locked'] === 1) {
    redirectWithError("Your document is currently being reviewed by the personnel and cannot be changed.", $redirect_url);
}

// 3. Process Receipt
if ($doReceipt) {
    $receiptFilename = "receipt_{$student_id}_R{$round}_" . time() . "." . $receiptExt;
    $receiptDir = "../../../uploads/grammarly_ai/receipts/";
    if (!is_dir($receiptDir)) mkdir($receiptDir, 0755, true);
    
    if (!move_uploaded_file($_FILES['receipt_file']['tmp_name'], $receiptDir . $receiptFilename)) {
        redirectWithError("Failed to upload receipt.", $redirect_url);
    }

    $tCheck = $conn->query("SELECT id FROM grammarly_ai_transactions WHERE student_id = $student_id AND round = $round");
    if ($tCheck->num_rows > 0) {
        $transStmt = $conn->prepare("UPDATE grammarly_ai_transactions SET receipt_path = ?, status = 'Receipt Uploaded' WHERE student_id = ? AND round = ?");
        $transStmt->bind_param("sii", $receiptFilename, $student_id, $round);
    } else {
        $transStmt = $conn->prepare("INSERT INTO grammarly_ai_transactions (student_id, round, receipt_path, status) VALUES (?, ?, ?, 'Receipt Uploaded')");
        $transStmt->bind_param("iis", $student_id, $round, $receiptFilename);
    }
    $transStmt->execute();
    $transStmt->close();
}

// 4. Process Document
if ($doDocument) {
    $docFilename = "doc_{$student_id}_R{$round}_" . time() . "." . $docExt;
    $docDir = "../../../uploads/grammarly_ai/submissions/";
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);
    
    if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $docDir . $docFilename)) {
        redirectWithError("Failed to upload document.", $redirect_url);
    }

    if ($existingDoc) {
        $oldPath = $docDir . $existingDoc['file_path'];
        if (file_exists($oldPath)) unlink($oldPath); 

        // Update document AND reset the lock (is_locked = 0) and use active personnel
        $subStmt = $conn->prepare("UPDATE grammarly_ai SET file_path = ?, status = 'Pending', personnel_id = ?, is_locked = 0, uploaded_at = NOW() WHERE id = ?");
        $subStmt->bind_param("sii", $docFilename, $active_personnel_id, $existingDoc['id']);
    } else {
        $subStmt = $conn->prepare("INSERT INTO grammarly_ai (student_id, school_id, file_path, status, round, personnel_id, is_locked) VALUES (?, ?, ?, 'Pending', ?, ?, 0)");
        $subStmt->bind_param("issii", $student_id, $school_id, $docFilename, $round, $active_personnel_id);
    }
    $subStmt->execute();
    $subStmt->close();
}

// 5. Send Unified Email Notification
try {
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

    $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;'>";
    $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Notification.</p></div></div></div>";

    $uploadTypeText = "files";
    $titleText = "Upload Status Update";

    if ($doReceipt && $doDocument) {
        $uploadTypeText = "payment receipt and research document have both";
        $titleText = "Upload Successful";
    } elseif ($doReceipt) {
        $uploadTypeText = "payment receipt has";
        $titleText = "Receipt Updated";
    } elseif ($doDocument) {
        $uploadTypeText = "research document has";
        $titleText = "Document Updated";
    }

    // Email to Student
    $mail->addAddress($studentEmail, $studentName);
    $mail->Subject = "Submission Received - Round $round";
    $mail->Body = $header . "<div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>$titleText</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>$studentName</strong>,</p><p>Your $uploadTypeText been successfully uploaded for Round $round.</p></div>" . $footer;
    $mail->send();

    // Email to Personnel
    $mail->clearAddresses();
    if ($active_personnel_id) {
        $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.id = ?");
        $stmtP->bind_param("i", $active_personnel_id);
    } else {
        $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.service_role = 'Grammarly & AI Checking'");
    }
    $stmtP->execute();
    $resP = $stmtP->get_result();
    while($p = $resP->fetch_assoc()) {
        $mail->addAddress($p['email'], $p['full_name']);
    }
    
    if ($resP->num_rows > 0) {
        $mail->Subject = "ACTION REQUIRED: Submission Update Round $round - $controlNo";
        $mail->Body = $header . "
            <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Submission Updated</h1></div>
            <div style='padding:30px;line-height:1.6;color:#334155;'>
                <p>A student has updated their files for review.</p>
                <div style='background:#eff6ff; border-left:4px solid #2563eb; padding:15px; margin:20px 0;'>
                    <p style='margin:0;'><strong>Student:</strong> $studentName</p>
                    <p style='margin:0;'><strong>Round:</strong> $round</p>
                    <p style='margin:0;'><strong>Update Type:</strong> $titleText</p>
                </div>
                <p>Please log in to the dashboard to process this submission.</p>
            </div>" . $footer;
        $mail->send();
    }
} catch (Exception $e) { }

$_SESSION['flash_success'] = "Files successfully uploaded!";
header("Location: " . $success_url);
exit();
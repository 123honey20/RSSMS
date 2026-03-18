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

function redirectWithError($message, $url) {
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// 1. Get student and user details
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

// 2. File handling
if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file to upload.", $redirect_url);
}

$file = $_FILES['submission_file'];
$originalName = basename($file['name']);
$filename = time() . "_" . $originalName;
$targetDir = "../../../uploads/grammarly_ai/submissions/";
$targetFile = $targetDir . $filename;

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'docx', 'doc', 'odt', 'rtf', 'txt', 'pptx'])) {
    redirectWithError("Invalid file extension.", $redirect_url);
}

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    
    // Determine the round from approved transaction
    $stmt = $conn->prepare("SELECT round FROM grammarly_ai_transactions WHERE student_id = ? AND status = 'Approved' ORDER BY round DESC LIMIT 1");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $approvedTransaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$approvedTransaction) {
        unlink($targetFile);
        redirectWithError("No approved transaction found.", $redirect_url);
    }

    $round = (int)$approvedTransaction['round'];

    // Check for existing submission and handler (personnel_id)
    $checkSubmission = $conn->prepare("SELECT id, file_path, status, personnel_id FROM grammarly_ai WHERE student_id = ? AND round = ? LIMIT 1");
    $checkSubmission->bind_param("ii", $student_id, $round);
    $checkSubmission->execute();
    $existingSubmission = $checkSubmission->get_result()->fetch_assoc();
    $checkSubmission->close();

    $assigned_personnel_id = null;

    if ($existingSubmission) {
        $assigned_personnel_id = $existingSubmission['personnel_id']; // The "Locked" Personnel

        if ($existingSubmission['status'] === 'Pending') {
            $oldPath = $targetDir . $existingSubmission['file_path'];
            if (file_exists($oldPath)) unlink($oldPath);

            $stmt = $conn->prepare("UPDATE grammarly_ai SET file_path = ?, status = 'Pending', uploaded_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $filename, $existingSubmission['id']);
            $stmt->execute();
        } elseif ($existingSubmission['status'] === 'Approved') {
            unlink($targetFile);
            redirectWithError("This round is already approved.", $redirect_url);
        } else {
            // Re-uploading after Rejection
            $stmt = $conn->prepare("UPDATE grammarly_ai SET file_path = ?, status = 'Pending', uploaded_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $filename, $existingSubmission['id']);
            $stmt->execute();
        }
    } else {
        // First upload for this round
        $stmt = $conn->prepare("INSERT INTO grammarly_ai (student_id, school_id, file_path, status, round) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("issi", $student_id, $school_id, $filename, $round);
        $stmt->execute();
    }

    // --- EMAIL NOTIFICATION SYSTEM ---
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
        $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Grammarly & AI Checking Notification.</p></div></div></div>";

        // 1. Email to Student
        $mail->addAddress($studentEmail, $studentName);
        $mail->Subject = "Grammarly & AI Document Submitted - Round $round";
        $mail->Body = $header . "<div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Document Submitted</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>$studentName</strong>,</p><p>Your document for <strong>Grammarly & AI Checking</strong> has been uploaded for Round $round.</p></div>" . $footer;
        $mail->send();

        // 2. Email to Personnel (Targeted Logic)
        $mail->clearAddresses();
        if ($assigned_personnel_id) {
            // Only email the person who already handled it
            $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.id = ?");
            $stmtP->bind_param("i", $assigned_personnel_id);
        } else {
            // New round: Email all personnel in this service
            $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.service_role = 'Grammarly & AI Checking'");
        }
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while($p = $resP->fetch_assoc()) $mail->addAddress($p['email'], $p['full_name']);
        
        if ($resP->num_rows > 0) {
            $mail->Subject = "ACTION REQUIRED: Grammarly Submission Round $round - $controlNo";
            $mail->Body = $header . "
                <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>New Checking Task</h1></div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>A research document is ready for Grammarly & AI review.</p>
                    <div style='background:#eff6ff; border-left:4px solid #2563eb; padding:15px; margin:20px 0;'>
                        <p style='margin:0;'><strong>Student:</strong> $studentName</p>
                        <p style='margin:0;'><strong>Control No:</strong> $controlNo</p>
                        <p style='margin:0;'><strong>Round:</strong> $round</p>
                        <p style='margin:5px 0 0 0;'><strong>Title:</strong> $thesisTitle</p>
                    </div>
                    <p>Please log in to the dashboard to process this submission.</p>
                </div>" . $footer;
            $mail->send();
        }
    } catch (Exception $e) { error_log($e->getMessage()); }

    $_SESSION['flash_success'] = "Document submitted successfully.";
    header("Location: " . $redirect_url);
    exit();
}
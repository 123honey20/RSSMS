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
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_librarian";

function redirectWithError($message, $url, $fileToTrash = null) {
    if ($fileToTrash && file_exists($fileToTrash) && is_file($fileToTrash)) unlink($fileToTrash);
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// Fetch student data
$stmt = $conn->prepare("SELECT s.id, s.control_number, s.research_leader, s.thesis_title, s.department_id, u.email, u.school_id FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$student_id = $studentData['id'];
$school_id = $studentData['school_id'];
$studentEmail = $studentData['email'];
$studentName = $studentData['research_leader'];
$controlNo = $studentData['control_number'];
$thesisTitle = $studentData['thesis_title'];
$studentDeptId = $studentData['department_id'];
$stmt->close();

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file.", $redirect_url);
}

$file = $_FILES['submission_file'];
$originalName = basename($file['name']);
$filename = time() . "_" . $originalName;
$targetDir = "../../../uploads/librarian/";
$targetFile = $targetDir . $filename;

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf','docx','doc','odt','rtf','txt','pptx'])) redirectWithError("Invalid file type.", $redirect_url);

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    $checkStmt = $conn->prepare("SELECT * FROM librarian WHERE student_id = ? ORDER BY round DESC LIMIT 1");
    $checkStmt->bind_param("i", $student_id);
    $checkStmt->execute();
    $latest = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    $round = 1;
    $assigned_personnel_id = null; 

    if ($latest) {
        $assigned_personnel_id = $latest['personnel_id'];
        if ($latest['status'] === 'Pending') {
            if (file_exists($targetDir . $latest['file_path'])) unlink($targetDir . $latest['file_path']);
            $stmt = $conn->prepare("UPDATE librarian SET file_path = ?, status = 'Pending', uploaded_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $filename, $latest['id']);
            $stmt->execute();
            $round = $latest['round'];
        } elseif ($latest['status'] === 'Rejected') {
            if ($latest['round'] >= 7) redirectWithError("Maximum rounds reached.", $redirect_url, $targetFile);
            $round = $latest['round'] + 1;
            $stmt = $conn->prepare("INSERT INTO librarian (student_id, school_id, file_path, status, round, personnel_id) VALUES (?, ?, ?, 'Pending', ?, ?)");
            $stmt->bind_param("issii", $student_id, $school_id, $filename, $round, $assigned_personnel_id);
            $stmt->execute();
        } else {
            redirectWithError("Already approved.", $redirect_url, $targetFile);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO librarian (student_id, school_id, file_path, status, round) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("issi", $student_id, $school_id, $filename, $round);
        $stmt->execute();
    }

    // --- EMAIL LOGIC ---
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
        $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Librarian Notification.</p></div></div></div>";

        // 1. Email to Student
        $mail->addAddress($studentEmail, $studentName);
        $mail->Subject = "Librarian Review Document Submitted - Round $round";
        $mail->Body = $header . "<div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Document Submitted</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>$studentName</strong>,</p><p>Your document for <strong>Librarian review</strong> has been uploaded for Round $round.</p><p><strong>Control No:</strong> $controlNo</p></div>" . $footer;
        $mail->send();

        // 2. Email to Personnel (UPDATED TO USE JUNCTION TABLE)
        $mail->clearAddresses();
        $personnelEmailsFound = false;

        if ($assigned_personnel_id) {
            $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.id = ?");
            $stmtP->bind_param("i", $assigned_personnel_id);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            while($p = $resP->fetch_assoc()) {
                $mail->addAddress($p['email'], $p['full_name']);
                $personnelEmailsFound = true;
            }
        } else {
            $stmtP = $conn->prepare("
                SELECT u.email, p.full_name 
                FROM personnel_departments pd
                JOIN personnel p ON pd.user_id = p.user_id
                JOIN users u ON p.user_id = u.id
                WHERE p.service_role = 'Librarian' 
                AND pd.department_id = ?
            ");
            $stmtP->bind_param("i", $studentDeptId);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            while($p = $resP->fetch_assoc()) {
                $mail->addAddress($p['email'], $p['full_name']);
                $personnelEmailsFound = true;
            }
        }
        
        if ($personnelEmailsFound) {
            $mail->Subject = "ACTION REQUIRED: Librarian Submission Round $round - $controlNo";
            $mail->Body = $header . "
                <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>New Submission Task</h1></div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>A new research document is ready for librarian review from your department.</p>
                    <div style='background:#eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0;'>
                        <p style='margin:0;'><strong>Student:</strong> $studentName</p>
                        <p style='margin:0;'><strong>Round:</strong> $round</p>
                        <p style='margin:5px 0 0 0;'><strong>Title:</strong> $thesisTitle</p>
                    </div>
                    <p>Please log in to the dashboard to review.</p>
                </div>" . $footer;
            $mail->send();
        }
    } catch (Exception $e) { error_log($e->getMessage()); }

    $_SESSION['flash_success'] = "Document submitted successfully.";
    header("Location: " . $redirect_url);
    exit();
}
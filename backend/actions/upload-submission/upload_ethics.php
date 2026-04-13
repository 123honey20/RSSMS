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
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_ethics";

function redirectWithError($message, $url, $fileToTrash = null) {
    if ($fileToTrash && file_exists($fileToTrash) && is_file($fileToTrash)) unlink($fileToTrash);
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// Fetch student data including department name for the email
$stmt = $conn->prepare("
    SELECT s.id, s.control_number, s.research_leader, s.thesis_title, s.department_id, u.email, u.school_id, d.name as dept_name 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    JOIN departments d ON s.department_id = d.id
    WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();

$student_id = $studentData['id'];
$studentEmail = $studentData['email'];
$studentName = $studentData['research_leader'];
$controlNo = $studentData['control_number'];
$thesisTitle = $studentData['thesis_title'];
$studentDeptId = $studentData['department_id'];
$studentDeptName = $studentData['dept_name'];
$school_id = $studentData['school_id'];
$stmt->close();

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file to upload.", $redirect_url);
}

$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/ethics/";
$targetFile = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetFile)) {

    $checkStmt = $conn->prepare("SELECT * FROM ethics WHERE student_id = ? ORDER BY round DESC LIMIT 1");
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
            $stmt = $conn->prepare("UPDATE ethics SET file_path = ?, status = 'Pending', uploaded_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $filename, $latest['id']);
            $stmt->execute();
            $round = $latest['round'];
        } elseif ($latest['status'] === 'Needs Revision') {
            $round = (int)$latest['round'] + 1;
            $stmt = $conn->prepare("INSERT INTO ethics (student_id, school_id, file_path, status, round, personnel_id) VALUES (?, ?, ?, 'Pending', ?, ?)");
            $stmt->bind_param("issii", $student_id, $school_id, $filename, $round, $assigned_personnel_id);
            $stmt->execute();
        } else {
            redirectWithError("Already approved.", $redirect_url, $targetFile);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO ethics (student_id, school_id, file_path, status, round) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("issi", $student_id, $school_id, $filename, $round);
        $stmt->execute();
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuaalmodiel119@gmail.com';
        $mail->Password = 'nprf grsd yrxt auyz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Support');
        $mail->isHTML(true);

        $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;'>";
        $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>This is an automated system notification from RSSMS.</p></div></div></div>";

        // --- 1. Student Email ---
        $mail->addAddress($studentEmail, $studentName);
        $mail->Subject = "Ethics Clearance Submitted - Round $round";
        $mail->Body = $header . "
            <div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Document Submitted</h1></div>
            <div style='padding:30px;line-height:1.6;color:#334155;'>
                <p>Hello <strong>$studentName</strong>,</p>
                <p>Your document for <strong>Ethics Clearance (Round $round)</strong> has been successfully uploaded and is now awaiting review.</p>
                <p><strong>Control No:</strong> $controlNo</p>
            </div>" . $footer;
        $mail->send();

        // --- 2. Personnel Email (UPDATED TO USE JUNCTION TABLE) ---
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
                WHERE p.service_role = 'Ethics' 
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
            $mail->Subject = "ACTION REQUIRED: Ethics Review (Round $round) - $controlNo";
            $mail->Body = $header . "
                <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>New Ethics Submission</h1></div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>Dear Personnel,</p>
                    <p>A research document has been submitted for Ethics Review. Please see the details below:</p>
                    
                    <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin:20px 0;'>
                        <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                            <tr><td style='padding:8px 0; color:#64748b; width:120px;'>Student:</td><td style='font-weight:bold;'>$studentName</td></tr>
                            <tr><td style='padding:8px 0; color:#64748b;'>Control No:</td><td style='font-weight:bold;'>$controlNo</td></tr>
                            <tr><td style='padding:8px 0; color:#64748b;'>Department:</td><td>$studentDeptName</td></tr>
                            <tr><td style='padding:8px 0; color:#64748b;'>Round:</td><td><span style='background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px; font-weight:bold;'>Round $round</span></td></tr>
                            <tr><td style='padding:8px 0; color:#64748b;'>Thesis Title:</td><td style='font-style:italic;'>\"$thesisTitle\"</td></tr>
                        </table>
                    </div>

                    <div style='text-align:center; margin-top:25px;'>
                        <a href='' style='background:#2563eb; color:#fff; padding:12px 25px; text-decoration:none; border-radius:6px; font-weight:bold;'>Login to Dashboard</a>
                    </div>
                </div>" . $footer;
            $mail->send();
        }
    } catch (Exception $e) { error_log($e->getMessage()); }

    $_SESSION['flash_success'] = "Ethics document submitted successfully.";
    header("Location: " . $redirect_url);
    exit();
}
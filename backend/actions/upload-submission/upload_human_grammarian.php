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
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_human_grammarian";

function redirectWithError(string $message, string $url, ?string $fileToTrash = null) {
    if ($fileToTrash && file_exists($fileToTrash) && is_file($fileToTrash)) unlink($fileToTrash);
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// Fetch student data INCLUDING course_id
$stmt = $conn->prepare("SELECT s.id, s.control_number, s.research_leader, s.thesis_title, s.department_id, s.course_id, u.email, u.school_id, d.name as dept_name FROM students s JOIN users u ON s.user_id = u.id JOIN departments d ON s.department_id = d.id WHERE u.id = ?");
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
$studentCourseId = $studentData['course_id'];
$studentDeptName = $studentData['dept_name'];
$stmt->close();

// --- FETCH ADMIN RULES USING THE COURSE ID ---
$reqStmt = $conn->prepare("SELECT required_phases, round_limit_per_phase FROM course_service_requirements WHERE course_id = ? AND service_type = 'Human Grammarian'");
$reqStmt->bind_param("i", $studentCourseId);
$reqStmt->execute();
$reqRes = $reqStmt->get_result()->fetch_assoc();
$max_phases = $reqRes ? (int)$reqRes['required_phases'] : 1; 
$max_rounds = $reqRes ? (int)$reqRes['round_limit_per_phase'] : 7; 
$reqStmt->close();

// --- NEW: FETCH THE *CURRENT* ASSIGNED PERSONNEL AND EXTRA ROUNDS FROM ADMIN'S DASHBOARD ---
$assignStmt = $conn->prepare("SELECT assigned_personnel_id, extra_rounds FROM service_applications WHERE student_id = ? AND service_type = 'Human Grammarian' AND status = 'Approved'");
$assignStmt->bind_param("i", $student_id);
$assignStmt->execute();
$assignRes = $assignStmt->get_result()->fetch_assoc();
$active_personnel_id = $assignRes['assigned_personnel_id'] ?? null;
$extra_rounds = $assignRes ? (int)$assignRes['extra_rounds'] : 0;
$assignStmt->close();

// ADD GRANTED EXTRA ROUNDS TO THE LIMIT!
$max_rounds += $extra_rounds;

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file.", $redirect_url);
}

$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/human_grammarian/";
$targetFile = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    redirectWithError("Failed to upload document to the server.", $redirect_url);
}

// Get the latest submission
$checkStmt = $conn->prepare("SELECT * FROM human_grammarian WHERE student_id = ? ORDER BY phase DESC, round DESC LIMIT 1");
$checkStmt->bind_param("i", $student_id);
$checkStmt->execute();
$latest = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

$phase = 1;
$round = 1;

if ($latest) {
    $phase = (int)($latest['phase'] ?? 1);
    $round = (int)$latest['round'];

    // ENFORCE SECURITY LOCK
    if ($latest['status'] === 'Pending' && (int)$latest['is_locked'] === 1) {
        redirectWithError("Your document is currently being reviewed by the personnel and cannot be changed.", $redirect_url, $targetFile);
    }

    if ($latest['status'] === 'Pending') {
        if (file_exists($targetDir . $latest['file_path'])) unlink($targetDir . $latest['file_path']);
        
        // REASSIGNMENT UPDATE
        $stmt = $conn->prepare("UPDATE human_grammarian SET file_path = ?, status = 'Pending', personnel_id = ?, is_locked = 0, uploaded_at = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $filename, $active_personnel_id, $latest['id']);
        $stmt->execute();
        
    } elseif ($latest['status'] === 'Needs Revision') {
        if ($round >= $max_rounds) {
            redirectWithError("You have reached the maximum round limit ($max_rounds) for Phase $phase.", $redirect_url, $targetFile);
        }
        $round++; 
        $stmt = $conn->prepare("INSERT INTO human_grammarian (student_id, school_id, file_path, status, round, phase, personnel_id, is_locked) VALUES (?, ?, ?, 'Pending', ?, ?, ?, 0)");
        $stmt->bind_param("issiii", $student_id, $school_id, $filename, $round, $phase, $active_personnel_id);
        $stmt->execute();

    } elseif ($latest['status'] === 'Approved') {
        if ($phase >= $max_phases) {
            redirectWithError("You have already completed all required phases for Human Grammarian.", $redirect_url, $targetFile);
        }
        $phase++; 
        $round = 1; 
        $stmt = $conn->prepare("INSERT INTO human_grammarian (student_id, school_id, file_path, status, round, phase, personnel_id, is_locked) VALUES (?, ?, ?, 'Pending', ?, ?, ?, 0)");
        $stmt->bind_param("issiii", $student_id, $school_id, $filename, $round, $phase, $active_personnel_id);
        $stmt->execute();
    }
} else {
    $stmt = $conn->prepare("INSERT INTO human_grammarian (student_id, school_id, file_path, status, round, phase, personnel_id, is_locked) VALUES (?, ?, ?, 'Pending', ?, ?, ?, 0)");
    $stmt->bind_param("issiii", $student_id, $school_id, $filename, $round, $phase, $active_personnel_id);
    $stmt->execute();
}

$phaseText = $max_phases > 1 ? "Phase $phase, " : "";

// --- EMAIL LOGIC ---
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
    $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Human Grammarian Notification.</p></div></div></div>";

    // Student Email
    $mail->addAddress($studentEmail, $studentName);
    $mail->Subject = "Human Grammarian Document Submitted - {$phaseText}Round $round";
    $mail->Body = $header . "<div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Document Submitted</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>$studentName</strong>,</p><p>Your document for <strong>Human Grammarian Review</strong> has been uploaded for {$phaseText}Round $round.</p></div>" . $footer;
    $mail->send();

    // Personnel Email Logic
    $mail->clearAddresses();
    $personnelEmailsFound = false;

    if ($active_personnel_id) {
        $stmtP = $conn->prepare("SELECT u.email, p.full_name FROM users u JOIN personnel p ON u.id = p.user_id WHERE p.id = ?");
        $stmtP->bind_param("i", $active_personnel_id);
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
            WHERE p.service_role = 'Human Grammarian' 
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
        $mail->Subject = "ACTION REQUIRED: Submission {$phaseText}Round $round - $controlNo";
        $mail->Body = $header . "
            <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>New Submission Task</h1></div>
            <div style='padding:30px;line-height:1.6;color:#334155;'>
                <p>A document is ready for Grammar review.</p>
                <div style='background:#eff6ff; padding: 15px; margin: 20px 0;'>
                    <strong>Student:</strong> $studentName<br>
                    <strong>Control No:</strong> $controlNo<br>
                    <strong>Department:</strong> $studentDeptName<br>
                    <strong>Phase & Round:</strong> <span style='background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px; font-weight:bold;'>{$phaseText}Round $round</span><br>
                    <strong>Title:</strong> $thesisTitle
                </div>
            </div>" . $footer;
        $mail->send();
    }
} catch (Exception $e) { error_log($e->getMessage()); }

$_SESSION['flash_success'] = "Document submitted successfully.";
header("Location: " . $redirect_url);
exit();
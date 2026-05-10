<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../config/database.php";

require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user'];

// Find Student ID
$stmt = $conn->prepare("SELECT id, control_number FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];
$control_number = $student['control_number'];
$service_type = $_POST['service_type'] ?? '';
$requested_personnel_id = intval($_POST['requested_personnel_id']);
$contract_file_path = null;

// SECURITY LOCK: Only the Statistician service uses this manual application form now.
if ($service_type !== 'Statistician' || !$requested_personnel_id) {
    $_SESSION['flash_error'] = "Invalid request or service type.";
    header("Location: ../../frontend/dashboards/student_dashboard.php?page=students_rs_statistician");
    exit();
}

// FIX: Handle REQUIRED Contract File Upload
if (!isset($_FILES['contract_file']) || $_FILES['contract_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = "Please upload your signed contract to proceed.";
    header("Location: ../../frontend/dashboards/student_dashboard.php?page=students_rs_statistician");
    exit();
}

$file = $_FILES['contract_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// FIX: Expanded accepted file formats
$allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'];

if (in_array($ext, $allowed_exts)) {
    $filename = time() . "_contract_" . $student_id . "." . $ext;
    $targetDir = "../../uploads/contracts/";
    
    // Ensure directory exists
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        $contract_file_path = $filename;
    } else {
        $_SESSION['flash_error'] = "Failed to upload the contract file.";
        header("Location: ../../frontend/dashboards/student_dashboard.php?page=students_rs_statistician");
        exit();
    }
} else {
    $_SESSION['flash_error'] = "Invalid file format. Please upload PDF, DOC, DOCX, TXT, or images.";
    header("Location: ../../frontend/dashboards/student_dashboard.php?page=students_rs_statistician");
    exit();
}

// Insert into service_applications
$insertStmt = $conn->prepare("INSERT INTO service_applications (student_id, service_type, requested_personnel_id, contract_file_path, status) VALUES (?, ?, ?, ?, 'Pending')");
$insertStmt->bind_param("isis", $student_id, $service_type, $requested_personnel_id, $contract_file_path);

if ($insertStmt->execute()) {
    
    // FETCH INFO FOR EMAILS
    $stmtInfo = $conn->prepare("
        SELECT u.email, s.research_leader, p.full_name as personnel_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN personnel p ON p.id = ? 
        WHERE s.id = ?
    ");
    $stmtInfo->bind_param("ii", $requested_personnel_id, $student_id);
    $stmtInfo->execute();
    $info = $stmtInfo->get_result()->fetch_assoc();
    $stmtInfo->close();

    if ($info) {
        try {
            // Fetch Admin Email dynamically
            $adminQuery = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
            $adminEmail = $adminQuery->fetch_assoc()['email'] ?? 'joshuaalmodiel119@gmail.com';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'joshuaalmodiel119@gmail.com';
            $mail->Password   = 'nprf grsd yrxt auyz'; // Replace in production
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Support');
            $mail->isHTML(true);

            $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
            $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Application Notification.</p></div></div></div>";

            // --- 1. EMAIL NOTIFICATION TO STUDENT ---
            $mail->addAddress($info['email'], $info['research_leader']);
            $mail->Subject = "Application Submitted: Statistician Service";

            $mail->Body = $header . "
                <div style='background:#2563eb;padding:30px;text-align:center;'>
                    <h1 style='color:#fff;margin:0;font-size:22px;'>Application Received</h1>
                </div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                    <p>Your application for <strong>Statistician</strong> services has been successfully submitted and is currently pending Admin verification.</p>
                    <div style='background:#f8fafc; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0;'>
                        <p style='margin:0;'><strong>Requested Personnel:</strong> {$info['personnel_name']}</p>
                        <p style='margin:0;margin-top:5px;'><strong>Current Status:</strong> Pending Verification</p>
                    </div>
                    <p>We will notify you once the System Administrator formally verifies your contract and unlocks your dashboard.</p>
                </div>" . $footer;

            $mail->send();

            // --- 2. EMAIL NOTIFICATION TO ADMIN ---
            $mail->clearAddresses();
            $mail->addAddress($adminEmail, 'System Administrator');
            $mail->Subject = "ACTION REQUIRED: New Statistician Application";

            $mail->Body = $header . "
                <div style='background:#d97706;padding:30px;text-align:center;'>
                    <h1 style='color:#fff;margin:0;font-size:22px;'>Contract Review Required</h1>
                </div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>Hello Admin,</p>
                    <p>A student group has submitted a new application for <strong>Statistician</strong> services.</p>
                    <div style='background:#f8fafc; border-left: 4px solid #d97706; padding: 15px; margin: 20px 0;'>
                        <p style='margin:0 0 5px 0;'><strong>Control No:</strong> {$control_number}</p>
                        <p style='margin:0 0 5px 0;'><strong>Leader:</strong> {$info['research_leader']}</p>
                        <p style='margin:0;'><strong>Requested Personnel:</strong> {$info['personnel_name']}</p>
                    </div>
                    <p>The student has successfully uploaded their contract. Please log in to the Admin Dashboard under 'Personnel Requests' to review the contract and officially assign the personnel.</p>
                </div>" . $footer;

            $mail->send();

        } catch (Exception $e) {
            // Log error but proceed
            error_log("PHPMailer Error during application submission: " . $e->getMessage());
        }
    }

    $_SESSION['flash_success'] = "Application submitted! Waiting for Admin approval.";
} else {
    $_SESSION['flash_error'] = "Database error. Please try again.";
}

$insertStmt->close();
header("Location: ../../frontend/dashboards/student_dashboard.php?page=students_rs_statistician");
exit();
?>
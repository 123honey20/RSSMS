<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../config/database.php";

// NEW: Require PHPMailer
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app_id = intval($_POST['application_id']);
$assigned_id = intval($_POST['assigned_personnel_id']);
$action = $_POST['action']; // "Approve" or "Reject"

$status = ($action === 'Approve') ? 'Approved' : 'Rejected';

// If rejected, we don't assign a personnel ID so it resets.
if ($status === 'Rejected') {
    $assigned_id = NULL;
}

$stmt = $conn->prepare("UPDATE service_applications SET status = ?, assigned_personnel_id = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("sii", $status, $assigned_id, $app_id);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Application successfully " . strtolower($status) . "!";

    // EMAIL NOTIFICATIONS
    
    // Fetch information required for emails
    $infoStmt = $conn->prepare("
        SELECT sa.service_type, s.control_number, s.research_leader, u_stu.email as student_email,
               p.full_name as personnel_name, u_per.email as personnel_email
        FROM service_applications sa
        JOIN students s ON sa.student_id = s.id
        JOIN users u_stu ON s.user_id = u_stu.id
        LEFT JOIN personnel p ON p.id = ?
        LEFT JOIN users u_per ON p.user_id = u_per.id
        WHERE sa.id = ?
    ");
    // Bind assigned_id (even if NULL, it binds correctly)
    $infoStmt->bind_param("ii", $assigned_id, $app_id);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    $infoStmt->close();

    if ($info) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'joshuaalmodiel119@gmail.com'; // System email
            $mail->Password   = 'nprf grsd yrxt auyz'; // Replace in production
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Admin');
            $mail->isHTML(true);

            $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
            $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated System Notification.</p></div></div></div>";

            // --- 1. Email the Student (Result of Application) ---
            $mail->addAddress($info['student_email'], $info['research_leader']);
            $mail->Subject = "Application Status: " . $info['service_type'];

            if ($status === 'Approved') {
                $mail->Body = $header . "
                    <div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Application Approved</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                        <p>Your application for <strong>{$info['service_type']}</strong> has been <strong style='color:#059669;'>Approved</strong>.</p>
                        <div style='background:#f8fafc; border-left: 4px solid #059669; padding: 15px; margin: 20px 0;'>
                            <p style='margin:0;'><strong>Assigned Personnel:</strong> {$info['personnel_name']}</p>
                        </div>
                        <p>You may now log in to your dashboard to upload your documents.</p>
                    </div>" . $footer;
            } else {
                // Rejected email
                $mail->Body = $header . "
                    <div style='background:#dc2626;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Application Rejected</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                        <p>Your application for <strong>{$info['service_type']}</strong> has been <strong style='color:#dc2626;'>Rejected</strong>.</p>
                        <p>Please review your requirements and submit a new request if necessary.</p>
                    </div>" . $footer;
            }
            $mail->send();

            // --- 2. Email the Assigned Personnel (Only if Approved) ---
            if ($status === 'Approved' && $info['personnel_email']) {
                $mail->clearAddresses();
                $mail->addAddress($info['personnel_email'], $info['personnel_name']);
                $mail->Subject = "New Student Assigned: " . $info['service_type'];
                
                $mail->Body = $header . "
                    <div style='background:#1e3a8a;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>New Assignment Alert</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$info['personnel_name']}</strong>,</p>
                        <p>You have been assigned to handle the <strong>{$info['service_type']}</strong> review for a student.</p>
                        <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin:20px 0;'>
                            <p style='margin:0 0 10px 0;'><strong>Control No:</strong> {$info['control_number']}</p>
                            <p style='margin:0;'><strong>Leader:</strong> {$info['research_leader']}</p>
                        </div>
                        <p>The student will upload their documents to your queue shortly.</p>
                    </div>" . $footer;
                $mail->send();
            }

        } catch (Exception $e) {
            error_log("PHPMailer Error during process application: " . $e->getMessage());
        }
    }

} else {
    $_SESSION['flash_error'] = "Failed to update application.";
}

$stmt->close();
header("Location: ../../frontend/dashboards/admin_dashboard.php?page=personnel_requests");
exit();
?>
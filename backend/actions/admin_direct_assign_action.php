<?php
session_start();
require_once "../config/database.php";

// Require PHPMailer
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ADDED: Tell the browser to expect a JSON response
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $service_type = $_POST['service_type'];
    $assigned_id = intval($_POST['assigned_personnel_id']);

    if (!$student_id || !$service_type || !$assigned_id) {
        echo json_encode(["success" => false, "message" => "Missing assignment details."]);
        exit();
    }

    $is_success = false;
    $msg = "";

    // Check if an application already exists for this student and service
    $checkStmt = $conn->prepare("SELECT id FROM service_applications WHERE student_id = ? AND service_type = ?");
    $checkStmt->bind_param("is", $student_id, $service_type);
    $checkStmt->execute();
    $res = $checkStmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Update existing record and stamp time!
        $app_id = $row['id'];
        $updateStmt = $conn->prepare("UPDATE service_applications SET assigned_personnel_id = ?, status = 'Approved', updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("ii", $assigned_id, $app_id);
        
        if ($updateStmt->execute()) {
            $is_success = true;
            $msg = "$service_type Personnel successfully re-assigned!";
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update assignment."]);
            exit();
        }
        $updateStmt->close();
    } else {
        // Insert new record directly into "Approved" status and stamp time!
        $insertStmt = $conn->prepare("INSERT INTO service_applications (student_id, service_type, assigned_personnel_id, status, updated_at) VALUES (?, ?, ?, 'Approved', NOW())");
        $insertStmt->bind_param("isi", $student_id, $service_type, $assigned_id);
        
        if ($insertStmt->execute()) {
            $is_success = true;
            $msg = "$service_type Personnel officially assigned!";
        } else {
            echo json_encode(["success" => false, "message" => "Failed to assign personnel."]);
            exit();
        }
        $insertStmt->close();
    }
    
    $checkStmt->close();

    // ============================================
    // SEND EMAIL NOTIFICATIONS IF SUCCESSFUL
    // ============================================
    if ($is_success) {
        // Fetch detailed info required for the emails
        $infoStmt = $conn->prepare("
            SELECT s.control_number, s.research_leader, s.thesis_title, u_stu.email as student_email,
                   p.full_name as personnel_name, u_per.email as personnel_email
            FROM students s
            JOIN users u_stu ON s.user_id = u_stu.id
            JOIN personnel p ON p.id = ?
            JOIN users u_per ON p.user_id = u_per.id
            WHERE s.id = ?
        ");
        $infoStmt->bind_param("ii", $assigned_id, $student_id);
        $infoStmt->execute();
        $info = $infoStmt->get_result()->fetch_assoc();
        $infoStmt->close();

        if ($info) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'joshuaalmodiel119@gmail.com'; // Admin/System Email
                $mail->Password   = 'nprf grsd yrxt auyz'; // Replace with production app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS System Admin');
                $mail->isHTML(true);

                $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
                $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Assignment Notification.</p></div></div></div>";

                // --- 1. Send Email to the Student ---
                $mail->addAddress($info['student_email'], $info['research_leader']);
                $mail->Subject = "Personnel Assigned: $service_type";
                $mail->Body = $header . "
                    <div style='background:#059669;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Personnel Assigned</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                        <p>The System Administrator has officially assigned personnel for your <strong>$service_type</strong> review.</p>
                        <div style='background:#f8fafc; border-left: 4px solid #059669; padding: 15px; margin: 20px 0;'>
                            <p style='margin:0;'><strong>Assigned Personnel:</strong> {$info['personnel_name']}</p>
                            <p style='margin:0;margin-top:5px;'><strong>Status:</strong> Approved & Unlocked</p>
                        </div>
                        <p>You may now log in to your dashboard to upload your documents for review.</p>
                    </div>" . $footer;
                $mail->send();

                // --- 2. Send Email to the Assigned Personnel ---
                $mail->clearAddresses();
                $mail->addAddress($info['personnel_email'], $info['personnel_name']);
                $mail->Subject = "New Student Assigned: $service_type";
                $mail->Body = $header . "
                    <div style='background:#1e3a8a;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>New Assignment</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$info['personnel_name']}</strong>,</p>
                        <p>You have been assigned to handle the <strong>$service_type</strong> review for a new student group.</p>
                        <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin:20px 0;'>
                            <h3 style='margin-top:0; color:#334155; border-bottom:1px solid #e2e8f0; padding-bottom:10px;'>Student Details</h3>
                            <table style='width:100%; font-size:14px; border-collapse:collapse;'>
                                <tr><td style='padding:8px 0; color:#64748b;'>Control No:</td><td style='font-weight:bold;'>{$info['control_number']}</td></tr>
                                <tr><td style='padding:8px 0; color:#64748b;'>Leader:</td><td>{$info['research_leader']}</td></tr>
                                <tr><td style='padding:8px 0; color:#64748b;'>Thesis Title:</td><td style='font-style:italic;'>\"{$info['thesis_title']}\"</td></tr>
                            </table>
                        </div>
                        <p style='font-size:13px; color:#64748b;'>The student has been notified and will upload their documents to your queue shortly.</p>
                    </div>" . $footer;
                $mail->send();

            } catch (Exception $e) {
                error_log("PHPMailer Error during personnel assignment: " . $e->getMessage());
            }
        }
    }
    
    // Echo the JSON Success Response!
    echo json_encode(["success" => true, "message" => $msg]);
    exit();
}
?>
<?php
session_start();
require_once "../config/database.php";
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = $_POST['service_type'] ?? '';
    $from_personnel_id = intval($_POST['from_personnel_id']);
    $to_personnel_id = intval($_POST['to_personnel_id']);
    $student_ids = $_POST['student_ids'] ?? [];

    if (empty($service_type) || !$to_personnel_id || empty($student_ids)) {
        $_SESSION['flash_error'] = "Missing reassignment details.";
        header("Location: ../../frontend/dashboards/admin_dashboard.php?page=reassign_workload");
        exit();
    }

    $success_count = 0;
    $transferred_students_info = [];

    // 1. UPDATE DATABASE AND LOG HISTORY
    foreach ($student_ids as $sid) {
        $sid = intval($sid);
        $updateStmt = $conn->prepare("UPDATE service_applications SET assigned_personnel_id = ? WHERE student_id = ? AND service_type = ? AND assigned_personnel_id = ?");
        $updateStmt->bind_param("iisi", $to_personnel_id, $sid, $service_type, $from_personnel_id);
        
        if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
            $success_count++;

            // INSERT INTO HISTORY LOG
            $logStmt = $conn->prepare("INSERT INTO reassignment_logs (student_id, service_type, from_personnel_id, to_personnel_id) VALUES (?, ?, ?, ?)");
            $logStmt->bind_param("isii", $sid, $service_type, $from_personnel_id, $to_personnel_id);
            $logStmt->execute();
            $logStmt->close();

            // Fetch info to send email to this specific student
            $infoStmt = $conn->prepare("SELECT control_number, research_leader, thesis_title, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
            $infoStmt->bind_param("i", $sid);
            $infoStmt->execute();
            $infoRes = $infoStmt->get_result()->fetch_assoc();
            $infoStmt->close();

            if ($infoRes) {
                $transferred_students_info[] = $infoRes;
            }
        }
        $updateStmt->close();
    }

    // 2. SEND EMAILS IF SUCCESSFUL
    if ($success_count > 0) {
        $_SESSION['flash_success'] = "Successfully transferred $success_count student(s) to the new personnel!";

        // Get details of the NEW personnel
        $pStmt = $conn->prepare("SELECT p.full_name, u.email FROM personnel p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $pStmt->bind_param("i", $to_personnel_id);
        $pStmt->execute();
        $newPersonnel = $pStmt->get_result()->fetch_assoc();
        $pStmt->close();

        if ($newPersonnel) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'joshuaalmodiel119@gmail.com'; 
                $mail->Password   = 'nprf grsd yrxt auyz'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS System Admin');
                $mail->isHTML(true);

                $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
                $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Reassignment Notice.</p></div></div></div>";

                // --- Email the New Personnel ---
                $mail->addAddress($newPersonnel['email'], $newPersonnel['full_name']);
                $mail->Subject = "Workload Update: $success_count New Students Assigned";
                
                $studentListHTML = "";
                foreach($transferred_students_info as $stu) {
                    $studentListHTML .= "<li><strong>{$stu['control_number']}</strong> - {$stu['research_leader']}</li>";
                }

                $mail->Body = $header . "
                    <div style='background:#d97706;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Workload Reassigned</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$newPersonnel['full_name']}</strong>,</p>
                        <p>Due to schedule adjustments, you have been reassigned <strong>$success_count</strong> new student(s) for <strong>$service_type</strong> review.</p>
                        <div style='background:#fffbeb; border-left: 4px solid #d97706; padding: 15px; margin: 20px 0;'>
                            <ul style='margin:0; padding-left:20px;'>$studentListHTML</ul>
                        </div>
                        <p>These students have been moved to your active dashboard.</p>
                    </div>" . $footer;
                $mail->send();

                // --- Email the Students ---
                $mail->clearAddresses();
                $mail->Subject = "Reviewer Reassigned: $service_type";
                
                foreach ($transferred_students_info as $stu) {
                    $mail->clearAddresses();
                    $mail->addAddress($stu['email'], $stu['research_leader']);
                    $mail->Body = $header . "
                        <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Reviewer Update</h1></div>
                        <div style='padding:30px;line-height:1.6;color:#334155;'>
                            <p>Hello <strong>{$stu['research_leader']}</strong>,</p>
                            <p>Your <strong>$service_type</strong> reviewer has been officially reassigned to ensure timely processing of your document.</p>
                            <div style='background:#eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0;'>
                                <p style='margin:0;'><strong>New Assigned Personnel:</strong> {$newPersonnel['full_name']}</p>
                            </div>
                            <p>Please note: All your past progress and uploads are safe and have been automatically transferred to your new reviewer.</p>
                        </div>" . $footer;
                    $mail->send();
                }

            } catch (Exception $e) {
                error_log("PHPMailer Error during reassignment: " . $e->getMessage());
            }
        }
    } else {
        $_SESSION['flash_error'] = "No records were updated. They may have already been moved.";
    }

    header("Location: ../../frontend/dashboards/admin_dashboard.php?page=reassign_workload");
    exit();
}
?>
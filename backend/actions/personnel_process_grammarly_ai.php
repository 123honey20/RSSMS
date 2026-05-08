<?php
session_start();
require_once "../config/database.php";
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = intval($_POST['submission_id']);
    $action = $_POST['action'] ?? '';

    // ==========================================
    // STEP 1: RECEIPT PROCESSING
    // ==========================================
    if ($action === 'Approve Receipt' || $action === 'Reject Receipt') {
        
        $transStatus = ($action === 'Approve Receipt') ? 'Approved' : 'Needs Revision';
        
        $infoStmt = $conn->prepare("SELECT student_id, round FROM grammarly_ai WHERE id = ?");
        $infoStmt->bind_param("i", $submission_id);
        $infoStmt->execute();
        $subInfo = $infoStmt->get_result()->fetch_assoc();
        $infoStmt->close();
        
        if ($subInfo) {
            $student_id = $subInfo['student_id'];
            $round = $subInfo['round'];
            
            // Update Transaction Table
            $transStmt = $conn->prepare("UPDATE grammarly_ai_transactions SET status = ? WHERE student_id = ? AND round = ?");
            $transStmt->bind_param("sii", $transStatus, $student_id, $round);
            $success = $transStmt->execute();
            $transStmt->close();
            
            if ($success) {
                $_SESSION['flash_success'] = "Receipt $transStatus successfully!";
                
                // Get Email Info
                $stmtDetails = $conn->prepare("SELECT s.research_leader, u.email as student_email, s.control_number FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
                $stmtDetails->bind_param("i", $student_id);
                $stmtDetails->execute();
                $info = $stmtDetails->get_result()->fetch_assoc();
                
                if ($info) {
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

                        $themeColor = ($transStatus === 'Approved') ? '#059669' : '#dc2626';
                        $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;'>";
                        $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Grammarly & AI Checking Log.</p></div></div></div>";

                        $mail->addAddress($info['student_email'], $info['research_leader']);
                        $mail->Subject = "Receipt $transStatus - Round $round";
                        
                        if ($transStatus === 'Approved') {
                            $mail->Body = $header . "<div style='background:$themeColor;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Receipt Approved</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>{$info['research_leader']}</strong>,</p><p>Your payment receipt for Round $round has been verified and <strong style='color:$themeColor;'>Approved</strong>. Our personnel will now proceed to review your research document.</p></div>" . $footer;
                        } else {
                            $mail->Body = $header . "<div style='background:$themeColor;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Receipt Rejected</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>{$info['research_leader']}</strong>,</p><p>Your payment receipt for Round $round was <strong style='color:$themeColor;'>Rejected (Needs Revision)</strong>. Please log into your dashboard and re-upload a clear, valid receipt so we can review your document.</p></div>" . $footer;
                        }
                        $mail->send();
                    } catch (Exception $e) {}
                }
            } else {
                $_SESSION['flash_error'] = "Database error. Please try again.";
            }
        }
    }
    
    // ==========================================
    // STEP 2: DOCUMENT PROCESSING
    // ==========================================
    elseif ($action === 'Approve' || $action === 'Needs Revision') {
        
        $status = ($action === 'Approve') ? 'Approved' : 'Needs Revision';
        $filename = null; 
        $has_file = isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK;

        if ($has_file) {
            $file = $_FILES['result_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['docx', 'pdf', 'txt', 'csv', 'xlsx', 'sav', 'png', 'jpg'];

            if (!in_array($ext, $allowed_exts)) {
                $_SESSION['flash_error'] = "Invalid file type. Allowed: .docx, .pdf, .txt, .csv, .xlsx, .sav, .png, .jpg";
                header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_grammarly_ai");
                exit();
            }
            
            $filename = "result_" . time() . "_" . $submission_id . "." . $ext;
            $targetDir = "../../uploads/grammarly_ai_results/";
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }

            if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
                $_SESSION['flash_error'] = "Failed to save the uploaded file.";
                header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_grammarly_ai");
                exit();
            }
        }

        // Update Document Table (AND SAVE `updated_at` TIMESTMAP)
        if ($filename) {
            $stmt = $conn->prepare("UPDATE grammarly_ai SET status = ?, result_file_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $filename, $submission_id);
        } else {
            $stmt = $conn->prepare("UPDATE grammarly_ai SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $submission_id);
        }
        $success = $stmt->execute();
        $stmt->close();

        // Email Notification
        if ($success) {
            $_SESSION['flash_success'] = "Document $status successfully!";

            $user_id = $_SESSION['user']; 
            $p_stmt = $conn->prepare("SELECT p.full_name, u.email FROM personnel p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
            $p_stmt->bind_param("i", $user_id);
            $p_stmt->execute();
            $personnel = $p_stmt->get_result()->fetch_assoc();
            $personnel_name = $personnel['full_name'];
            $personnel_email = $personnel['email'];

            $stmtDetails = $conn->prepare("
                SELECT g.round, s.research_leader, s.thesis_title, u.email as student_email, s.control_number
                FROM grammarly_ai g JOIN students s ON g.student_id = s.id JOIN users u ON s.user_id = u.id WHERE g.id = ?
            ");
            $stmtDetails->bind_param("i", $submission_id);
            $stmtDetails->execute();
            $info = $stmtDetails->get_result()->fetch_assoc();

            if ($personnel && $info) {
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

                    $themeColor = ($status === 'Approved') ? '#059669' : '#dc2626';
                    $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;'>";
                    $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p>Automated Grammarly & AI Checking Log.</p></div></div></div>";

                    $mail->addAddress($info['student_email'], $info['research_leader']);
                    $mail->Subject = "Document Review Result: $status";
                    $mail->Body = $header . "<div style='background:$themeColor;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Document $status</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>{$info['research_leader']}</strong>,</p><p>Your research document for Round {$info['round']} has been <strong style='color:$themeColor;'>$status</strong>. You can now download the result from your dashboard.</p></div>" . $footer;
                    $mail->send();

                    $mail->clearAddresses();
                    $mail->addAddress($personnel_email, $personnel_name);
                    $mail->Subject = "Action Logged: Document Review Complete - $status";
                    $mail->Body = $header . "<div style='background:#334155;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Action Confirmed</h1></div><div style='padding:30px;line-height:1.6;color:#334155;'><p>Hello <strong>$personnel_name</strong>,</p><p>You have successfully processed the research document for {$info['research_leader']} (Round {$info['round']}) as <strong style='color:$themeColor;'>$status</strong>.</p></div>" . $footer;
                    $mail->send();
                } catch (Exception $e) {}
            }
        } else {
            $_SESSION['flash_error'] = "Database error. Please try again.";
        }
    }

    header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_grammarly_ai");
    exit();
}
?>
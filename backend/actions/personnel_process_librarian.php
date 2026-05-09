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
    $action = $_POST['action'] ?? ''; // 'Approve' or 'Needs Revision'

    if ($action === 'Approve' || $action === 'Needs Revision') {
        
        $status = ($action === 'Approve') ? 'Approved' : 'Needs Revision';
        $filename = null; 
        $has_file = isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK;

        // --- 1. HANDLE FILE UPLOAD ---
        if ($has_file) {
            $file = $_FILES['result_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['docx', 'pdf', 'txt', 'csv', 'xlsx', 'sav', 'png', 'jpg'];

            if (!in_array($ext, $allowed_exts)) {
                $_SESSION['flash_error'] = "Invalid file type. Allowed: .docx, .pdf, .txt, .csv, .xlsx, .sav, .png, .jpg";
                header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_librarian");
                exit();
            }
            
            // Generate safe unique filename
            $filename = "result_" . time() . "_" . $submission_id . "." . $ext;
            
            // Define target directory
            $targetDir = "../../uploads/librarian_results/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
                $_SESSION['flash_error'] = "Failed to save the uploaded file.";
                header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_librarian");
                exit();
            }
        }

        // --- 2. UPDATE DATABASE (WITH TIMESTAMP) ---
        if ($filename) {
            $stmt = $conn->prepare("UPDATE librarian SET status = ?, result_file_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $filename, $submission_id);
        } else {
            $stmt = $conn->prepare("UPDATE librarian SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $submission_id);
        }
        
        $success = $stmt->execute();
        $stmt->close();

        // --- 3. SEND EMAIL NOTIFICATIONS ---
        if ($success) {
            $_SESSION['flash_success'] = "Submission $status successfully!";

            // Fetch Personnel Info
            $user_id = $_SESSION['user']; 
            $p_stmt = $conn->prepare("SELECT p.id, p.full_name, u.email FROM personnel p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
            $p_stmt->bind_param("i", $user_id);
            $p_stmt->execute();
            $personnel = $p_stmt->get_result()->fetch_assoc();
            $personnel_name = $personnel['full_name'];
            $personnel_email = $personnel['email'];

            // Fetch Student/Submission Info (Now including student_id!)
            $stmtDetails = $conn->prepare("
                SELECT g.round, g.phase, g.student_id, s.research_leader, s.thesis_title, u.email as student_email, s.control_number, d.name as dept_name
                FROM librarian g 
                JOIN students s ON g.student_id = s.id 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE g.id = ?
            ");
            $stmtDetails->bind_param("i", $submission_id);
            $stmtDetails->execute();
            $info = $stmtDetails->get_result()->fetch_assoc();

            if ($personnel && $info) {

                // FETCH MAX PHASES FOR THIS SPECIFIC COURSE
                $maxPhaseStmt = $conn->prepare("SELECT required_phases FROM course_service_requirements WHERE course_id = (SELECT course_id FROM students WHERE id = ?) AND service_type = 'Librarian'");
                $maxPhaseStmt->bind_param("i", $info['student_id']);
                $maxPhaseStmt->execute();
                $maxPhaseRes = $maxPhaseStmt->get_result()->fetch_assoc();
                $max_phases = $maxPhaseRes ? (int)$maxPhaseRes['required_phases'] : 1;
                $maxPhaseStmt->close();

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'joshuaalmodiel119@gmail.com';
                    $mail->Password   = 'nprf grsd yrxt auyz'; // Replace in production!
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Support');
                    $mail->isHTML(true);

                    $themeColor = ($status === 'Approved') ? '#059669' : '#dc2626';
                    $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
                    $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Librarian Service Log.</p></div></div></div>";
                    
                    // FIX: Only show phase text if the system supports more than 1 phase
                    $currentPhase = isset($info['phase']) ? (int)$info['phase'] : 1;
                    $phaseStr = ($max_phases > 1) ? "Phase {$currentPhase}, " : "";

                    // Email 1: To Student
                    $mail->addAddress($info['student_email'], $info['research_leader']);
                    $mail->Subject = "Librarian Review Result: $status";
                    $mail->Body = $header . "
                        <div style='background:$themeColor;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Review $status</h1></div>
                        <div style='padding:30px;line-height:1.6;color:#334155;'>
                            <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                            <p>Your document for <strong>Librarian Review</strong> has been officially <strong style='color:$themeColor;'>$status</strong>.</p>
                            <div style='background:#f8fafc; border-left: 4px solid $themeColor; padding: 15px; margin: 20px 0;'>
                                <p style='margin:0;'><strong>Control No:</strong> {$info['control_number']}</p>
                                <p style='margin:0;'><strong>Round:</strong> {$phaseStr}Round {$info['round']}</p>
                            </div>
                        </div>" . $footer;
                    $mail->send();

                    // Email 2: To Personnel (Log)
                    $mail->clearAddresses();
                    $mail->addAddress($personnel_email, $personnel_name);
                    $mail->Subject = "Action Logged: Librarian Review - $status";
                    $mail->Body = $header . "
                        <div style='background:#334155;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Review Action Confirmed</h1></div>
                        <div style='padding:30px;line-height:1.6;color:#334155;'>
                            <p>Hello <strong>$personnel_name</strong>,</p>
                            <p>This email confirms that you have processed a <strong>Librarian</strong> submission review:</p>
                            <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin:20px 0;'>
                                <h3 style='margin-top:0; color:#334155; border-bottom:1px solid #e2e8f0; padding-bottom:10px;'>Submission Details</h3>
                                <table style='width:100%; font-size:14px; border-collapse:collapse;'>
                                    <tr><td style='padding:8px 0; color:#64748b;'>Student:</td><td style='font-weight:bold;'>{$info['research_leader']}</td></tr>
                                    <tr><td style='padding:8px 0; color:#64748b;'>Department:</td><td>{$info['dept_name']}</td></tr>
                                    <tr><td style='padding:8px 0; color:#64748b;'>Round:</td><td>{$phaseStr}Round {$info['round']}</td></tr>
                                    <tr><td style='padding:8px 0; color:#64748b;'>Thesis Title:</td><td style='font-style:italic;'>\"{$info['thesis_title']}\"</td></tr>
                                    <tr><td style='padding:15px 0 8px 0; color:#64748b;'>Final Result:</td><td><span style='background:$themeColor; color:#fff; padding:4px 10px; border-radius:4px; font-weight:bold; font-size:12px; text-transform:uppercase;'>$status</span></td></tr>
                                </table>
                            </div>
                        </div>" . $footer;
                    $mail->send();
                } catch (Exception $e) { 
                    error_log("PHPMailer Error: " . $e->getMessage()); 
                }
            }
        } else {
            $_SESSION['flash_error'] = "Database error. Please try again.";
        }
    }

    header("Location: ../../frontend/dashboards/personnel_dashboard.php?page=submissions_librarian");
    exit();
}
?>
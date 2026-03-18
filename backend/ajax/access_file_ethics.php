<?php
session_start();
require_once "../config/database.php";
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user']; 

// Fetch personnel info
$p_stmt = $conn->prepare("SELECT p.id, p.full_name, u.email FROM personnel p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$personnel = $p_stmt->get_result()->fetch_assoc();

if (!$personnel) {
    echo json_encode(['success' => false, 'message' => 'Personnel not found']);
    exit;
}

$personnel_id = $personnel['id'];
$personnel_name = $personnel['full_name'];
$personnel_email = $personnel['email'];

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false]);
    exit;
}

// FETCH DETAILED INFO: Added JOINs for professional summary
$stmtDetails = $conn->prepare("
    SELECT 
        g.round, 
        s.research_leader, 
        s.thesis_title,
        u.email as student_email, 
        s.control_number,
        d.name as dept_name
    FROM ethics g 
    JOIN students s ON g.student_id = s.id 
    JOIN users u ON s.user_id = u.id 
    JOIN departments d ON s.department_id = d.id
    WHERE g.id = ?
");
$stmtDetails->bind_param("i", $id);
$stmtDetails->execute();
$info = $stmtDetails->get_result()->fetch_assoc();

// Update status AND lock the personnel_id for future rounds
$stmt = $conn->prepare("UPDATE ethics SET status = ?, personnel_id = ? WHERE id = ?");
$stmt->bind_param("sii", $status, $personnel_id, $id);
$success = $stmt->execute();

if ($success && $info) {
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
        $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
        $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Ethics Service Log.</p></div></div></div>";

        // --- 1. Student Email ---
        $mail->addAddress($info['student_email'], $info['research_leader']);
        $mail->Subject = "Ethics Clearance Result: $status";
        $mail->Body = $header . "
            <div style='background:$themeColor;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Review $status</h1></div>
            <div style='padding:30px;line-height:1.6;color:#334155;'>
                <p>Hello <strong>{$info['research_leader']}</strong>,</p>
                <p>Your document for <strong>Ethics Clearance</strong> has been officially <strong style='color:$themeColor;'>$status</strong>.</p>
                <div style='background:#f8fafc; border-left: 4px solid $themeColor; padding: 15px; margin: 20px 0;'>
                    <p style='margin:0;'><strong>Control No:</strong> {$info['control_number']}</p>
                    <p style='margin:0;'><strong>Round:</strong> {$info['round']}</p>
                </div>
            </div>" . $footer;
        $mail->send();

        // --- 2. Personnel Email (Professional Summary UI) ---
        $mail->clearAddresses();
        $mail->addAddress($personnel_email, $personnel_name);
        $mail->Subject = "Action Logged: Ethics Review - $status";
        $mail->Body = $header . "
            <div style='background:#334155;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Review Action Confirmed</h1></div>
            <div style='padding:30px;line-height:1.6;color:#334155;'>
                <p>Hello <strong>$personnel_name</strong>,</p>
                <p>This email confirms that you have processed an <strong>Ethics Clearance</strong> submission review:</p>
                <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin:20px 0;'>
                    <h3 style='margin-top:0; color:#334155; border-bottom:1px solid #e2e8f0; padding-bottom:10px;'>Submission Details</h3>
                    <table style='width:100%; font-size:14px; border-collapse:collapse;'>
                        <tr><td style='padding:8px 0; color:#64748b;'>Student:</td><td style='font-weight:bold;'>{$info['research_leader']}</td></tr>
                        <tr><td style='padding:8px 0; color:#64748b;'>Department:</td><td>{$info['dept_name']}</td></tr>
                        <tr><td style='padding:8px 0; color:#64748b;'>Round:</td><td>Round {$info['round']}</td></tr>
                        <tr><td style='padding:8px 0; color:#64748b;'>Thesis Title:</td><td style='font-style:italic;'>\"{$info['thesis_title']}\"</td></tr>
                        <tr><td style='padding:15px 0 8px 0; color:#64748b;'>Final Result:</td><td><span style='background:$themeColor; color:#fff; padding:4px 10px; border-radius:4px; font-weight:bold; font-size:12px; text-transform:uppercase;'>$status</span></td></tr>
                    </table>
                </div>
            </div>" . $footer;
        $mail->send();
    } catch (Exception $e) { error_log($e->getMessage()); }
}
echo json_encode(['success' => $success]);
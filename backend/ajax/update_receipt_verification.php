<?php
session_start();
require_once "../config/database.php";
require '../../vendor/autoload.php'; // Ensure path to PHPMailer is correct

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!in_array($status, ['Approved', 'Needs Revision'])) {
    echo json_encode(["success" => false, "message" => "Invalid Status"]);
    exit;
}

// 1. Get Transaction and Student details before updating
$stmtDetails = $conn->prepare("
    SELECT t.round, s.research_leader, u.email as student_email, s.control_number, p.full_name as personnel_name, pu.email as personnel_email
    FROM grammarly_ai_transactions t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN personnel p ON p.user_id = ? 
    JOIN users pu ON p.user_id = pu.id
    WHERE t.id = ?
");
// We use the logged-in personnel's session ID to get their name and email
$stmtDetails->bind_param("ii", $_SESSION['user'], $id);
$stmtDetails->execute();
$info = $stmtDetails->get_result()->fetch_assoc();

if (!$info) {
    echo json_encode(["success" => false, "message" => "Transaction data not found"]);
    exit;
}

// 2. Update the Database
$stmt = $conn->prepare("UPDATE grammarly_ai_transactions SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$dbSuccess = $stmt->execute();

if ($dbSuccess) {
    // =========================================================================
    // MODERN EMAIL NOTIFICATION SYSTEM
    // =========================================================================
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

        // Styling Variables
        $themeColor = ($status === 'Approved') ? '#10b981' : '#ef4444'; // Green for Appr, Red for Rej
        $emailHeader = "
            <div style='background-color: #f8fafc; padding: 20px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>";
        $emailFooter = "
                    <div style='background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b;'>
                        <p style='margin: 0;'>This is an automated notification. Please do not reply to this email.</p>
                        <p style='margin: 5px 0 0 0;'>&copy; 2026 Research Support Services Management System.</p>
                    </div>
                </div>
            </div>";

        // --- 1. EMAIL TO STUDENT ---
        $mail->addAddress($info['student_email'], $info['research_leader']);
        $mail->Subject = "Receipt Verification Update: {$status} (Round {$info['round']})";
        
        $actionText = ($status === 'Approved') 
            ? "Your payment receipt has been <strong>verified</strong>. You may now proceed to upload your research document in the Grammarly & AI Checking section."
            : "Your payment receipt was <strong>not accepted</strong>. Please check your dashboard for details and re-upload a clear copy of your receipt.";

        $mail->Body = $emailHeader . "
            <div style='background: {$themeColor}; padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Receipt {$status}</h1>
            </div>
            <div style='padding: 30px; line-height: 1.6; color: #334155;'>
                <p style='font-size: 18px;'>Hello <strong>{$info['research_leader']}</strong>,</p>
                <p>{$actionText}</p>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Control Number:</strong> {$info['control_number']}</p>
                    <p style='margin: 5px 0 0 0;'><strong>Service:</strong> Grammarly & AI Checking (Round {$info['round']})</p>
                </div>
                <a href='' style='display: inline-block; background: {$themeColor}; color: #ffffff; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px;'>Go to Dashboard</a>
            </div>" . $emailFooter;
        
        $mail->send();

        // --- 2. EMAIL TO PERSONNEL (CONFIRMATION) ---
        $mail->clearAddresses();
        $mail->addAddress($info['personnel_email'], $info['personnel_name']);
        $mail->Subject = "Confirmation: You marked Receipt #{$id} as {$status}";
        
        $mail->Body = $emailHeader . "
            <div style='background: #334155; padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Action Logged</h1>
            </div>
            <div style='padding: 30px; line-height: 1.6; color: #334155;'>
                <p style='font-size: 16px;'>Hello <strong>{$info['personnel_name']}</strong>,</p>
                <p>This email confirms that you have processed the following receipt:</p>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Student:</strong> {$info['research_leader']}</p>
                    <p style='margin: 5px 0;'><strong>Result:</strong> <span style='color: {$themeColor}; font-weight: bold;'>{$status}</span></p>
                    <p style='margin: 5px 0 0 0;'><strong>Control No:</strong> {$info['control_number']}</p>
                </div>
                <p>The student has been notified automatically.</p>
            </div>" . $emailFooter;

        $mail->send();

    } catch (Exception $e) {
        // Log mail error but keep the JSON response as success because DB updated
        error_log("Verification Email Error: " . $mail->ErrorInfo);
    }
}

echo json_encode(["success" => $dbSuccess]);
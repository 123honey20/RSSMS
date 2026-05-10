<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = (int)$_POST['application_id'];
    $service_type = $_POST['service_type'];

    // 1. Update DB to Pending
    $stmt = $conn->prepare("UPDATE service_applications SET round_request_status = 'Pending' WHERE id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();

    // 2. Fetch Admin Email & Student Info
    $adminStmt = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
    $adminEmail = $adminStmt->fetch_assoc()['email'] ?? 'admin@yoursystem.com';
    
    $studentStmt = $conn->query("SELECT s.control_number FROM service_applications sa JOIN students s ON sa.student_id = s.id WHERE sa.id = $application_id");
    $control_number = $studentStmt->fetch_assoc()['control_number'] ?? 'Unknown';

    // 3. Send Email to Admin
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuaalmodiel119@gmail.com';
        $mail->Password = 'nprf grsd yrxt auyz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Notification');
        $mail->addAddress($adminEmail);
        $mail->isHTML(true);
        $mail->Subject = "New Extra Round Request - $service_type";
        $mail->Body = "<h3>Extra Round Requested!</h3><p>Student Control Number: <strong>$control_number</strong> has exhausted their rounds for $service_type and is requesting an extension.</p><p>Please log in to the Admin Dashboard to Approve or Reject this request.</p>";
        $mail->send();
    } catch (Exception $e) {}

    $_SESSION['flash_success'] = "Request sent to Admin successfully!";
    $redirectPage = "students_rs_" . strtolower(str_replace(' ', '_', $service_type));
    header("Location: ../../../frontend/dashboards/student_dashboard.php?page=" . $redirectPage);
}
?>
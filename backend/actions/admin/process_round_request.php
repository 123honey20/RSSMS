<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";
require '../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = (int)$_POST['app_id'];
    $action = $_POST['action'];
    $extra = (int)$_POST['extra_rounds'];
    $studentEmail = $_POST['email'];

    if ($action === 'Approve') {
        $stmt = $conn->prepare("UPDATE service_applications SET extra_rounds = extra_rounds + ?, round_request_status = 'Approved' WHERE id = ?");
        $stmt->bind_param("ii", $extra, $app_id);
        $statusMsg = "Approved! $extra extra round(s) granted.";
    } else {
        $stmt = $conn->prepare("UPDATE service_applications SET round_request_status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $app_id);
        $statusMsg = "Rejected! Your request for extra rounds was denied.";
    }
    $stmt->execute();

    // Send email to student
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuaalmodiel119@gmail.com';
        $mail->Password = 'nprf grsd yrxt auyz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS Admin');
        $mail->addAddress($studentEmail);
        $mail->isHTML(true);
        $mail->Subject = "Update on your Round Request";
        $mail->Body = "<h3>Round Request Update</h3><p>Your request for an extension has been <strong>$statusMsg</strong></p>";
        $mail->send();
    } catch (Exception $e) {}

    $_SESSION['flash_success'] = "Request processed successfully.";
    header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=round_requests");
}
?>
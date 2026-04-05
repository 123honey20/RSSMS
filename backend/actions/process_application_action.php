<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../config/database.php";

$app_id = intval($_POST['application_id']);
$assigned_id = intval($_POST['assigned_personnel_id']);
$action = $_POST['action']; // "Approve" or "Reject"

$status = ($action === 'Approve') ? 'Approved' : 'Rejected';

// If rejected, we don't assign a personnel ID so it resets.
if ($status === 'Rejected') {
    $assigned_id = NULL;
}

$stmt = $conn->prepare("UPDATE service_applications SET status = ?, assigned_personnel_id = ? WHERE id = ?");
$stmt->bind_param("sii", $status, $assigned_id, $app_id);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Application successfully " . strtolower($status) . "!";
} else {
    $_SESSION['flash_error'] = "Failed to update application.";
}

$stmt->close();
header("Location: ../../frontend/dashboards/admin_dashboard.php?page=personnel_requests");
exit();
?>
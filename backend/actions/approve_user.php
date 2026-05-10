<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../config/database.php";

$user_id = $_POST['user_id'];

// Approve user
$stmt = $conn->prepare("UPDATE users SET status = 'Approved' WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "../../frontend/dashboards/admin_dashboard.php";
header("Location: " . $redirect_url);
exit();
?>
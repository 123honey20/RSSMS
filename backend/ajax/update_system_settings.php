<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['active_school_year']) || empty(trim($data['active_school_year']))) {
    echo json_encode(['success' => false, 'message' => 'School year is required.']);
    exit();
}

$active_sy = trim($data['active_school_year']);

$stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'active_school_year'");
$stmt->bind_param("s", $active_sy);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Global School Year applied!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update system.']);
}

$stmt->close();
?>
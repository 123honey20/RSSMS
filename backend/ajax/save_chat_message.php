<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON payload from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id'], $data['personnel_id'], $data['sender'], $data['service_type'], $data['message']) || empty(trim($data['message']))) {
    echo json_encode(['success' => false, 'error' => 'Missing required data or empty message.']);
    exit();
}

$student_id = intval($data['student_id']);
$personnel_id = intval($data['personnel_id']);
$sender = $data['sender']; // 'student' or 'personnel'
$service_type = $data['service_type'];
$message = trim($data['message']);

$stmt = $conn->prepare("INSERT INTO chat_messages (student_id, personnel_id, sender, service_type, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $student_id, $personnel_id, $sender, $service_type, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message saved.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save message.']);
}

$stmt->close();
?>
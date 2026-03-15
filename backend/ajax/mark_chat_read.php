<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id'], $data['personnel_id'], $data['service_type'], $data['reader'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$student_id = intval($data['student_id']);
$personnel_id = intval($data['personnel_id']);
$service_type = $data['service_type'];

// If personnel is reading, mark the STUDENT'S messages as read
$sender_to_mark = ($data['reader'] === 'personnel') ? 'student' : 'personnel';

$stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND personnel_id = ? AND service_type = ? AND sender = ? AND is_read = 0");
$stmt->bind_param("iiss", $student_id, $personnel_id, $service_type, $sender_to_mark);
$stmt->execute();

echo json_encode(['success' => true]);
$stmt->close();
?>
<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$personnel_id = isset($_GET['personnel_id']) ? intval($_GET['personnel_id']) : 0;
$service_type = isset($_GET['service_type']) ? $_GET['service_type'] : '';

if ($student_id === 0 || $personnel_id === 0 || empty($service_type)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

// Fetch messages ordered by oldest to newest
$stmt = $conn->prepare("
    SELECT sender, message, DATE_FORMAT(created_at, '%h:%i %p') as time_formatted 
    FROM chat_messages 
    WHERE student_id = ? AND personnel_id = ? AND service_type = ? 
    ORDER BY created_at ASC
");
$stmt->bind_param("iis", $student_id, $personnel_id, $service_type);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
$stmt->close();
?>
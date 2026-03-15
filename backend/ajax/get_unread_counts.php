<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$personnel_id = isset($_GET['personnel_id']) ? intval($_GET['personnel_id']) : 0;
$service_type = isset($_GET['service_type']) ? $_GET['service_type'] : '';

// Count unread messages sent BY the student TO this personnel
$stmt = $conn->prepare("
    SELECT student_id, COUNT(*) as unread_count 
    FROM chat_messages 
    WHERE personnel_id = ? AND service_type = ? AND sender = 'student' AND is_read = 0 
    GROUP BY student_id
");
$stmt->bind_param("is", $personnel_id, $service_type);
$stmt->execute();
$res = $stmt->get_result();

$counts = [];
while ($row = $res->fetch_assoc()) {
    $counts[$row['student_id']] = $row['unread_count'];
}

echo json_encode(['success' => true, 'counts' => $counts]);
$stmt->close();
?>
<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$service_type = isset($_GET['service_type']) ? $_GET['service_type'] : '';

// Count unread messages sent BY the personnel TO this student
$stmt = $conn->prepare("
    SELECT personnel_id, COUNT(*) as unread_count 
    FROM chat_messages 
    WHERE student_id = ? AND service_type = ? AND sender = 'personnel' AND is_read = 0 
    GROUP BY personnel_id
");
$stmt->bind_param("is", $student_id, $service_type);
$stmt->execute();
$res = $stmt->get_result();

$counts = [];
while ($row = $res->fetch_assoc()) {
    $counts[$row['personnel_id']] = $row['unread_count'];
}

echo json_encode(['success' => true, 'counts' => $counts]);
$stmt->close();
?>
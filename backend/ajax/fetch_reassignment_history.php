<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']); 
    exit;
}

$student_id = intval($_GET['student_id'] ?? 0);
$service = $_GET['service'] ?? '';

if (!$student_id || empty($service)) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// 1. Get all logs of transfers FIRST so we can check the earliest one
$logs = [];
$stmt2 = $conn->prepare("
    SELECT p1.full_name as from_personnel, p2.full_name as to_personnel, r.reassigned_at
    FROM reassignment_logs r
    LEFT JOIN personnel p1 ON r.from_personnel_id = p1.id
    LEFT JOIN personnel p2 ON r.to_personnel_id = p2.id
    WHERE r.student_id = ? AND r.service_type = ?
    ORDER BY r.reassigned_at ASC
");
$stmt2->bind_param("is", $student_id, $service);
$stmt2->execute();
$res = $stmt2->get_result();
while ($row = $res->fetch_assoc()) {
    $row['formatted_date'] = date('M d, Y - h:i A', strtotime($row['reassigned_at']));
    $logs[] = $row;
}
$stmt2->close();

// 2. Get the Application info
$stmt = $conn->prepare("
    SELECT 
        p_req.full_name as requested_personnel, 
        p_assign.full_name as current_personnel,
        sa.created_at as applied_date
    FROM service_applications sa
    LEFT JOIN personnel p_req ON sa.requested_personnel_id = p_req.id
    LEFT JOIN personnel p_assign ON sa.assigned_personnel_id = p_assign.id
    WHERE sa.student_id = ? AND sa.service_type = ?
");
$stmt->bind_param("is", $student_id, $service);
$stmt->execute();
$appData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. SMART LOGIC: Determine the Original Personnel
$original_personnel = 'Unknown/Not Found';
if ($appData) {
    if (!empty($appData['requested_personnel'])) {
        // If they explicitly requested someone during application
        $original_personnel = $appData['requested_personnel'];
    } elseif (count($logs) > 0) {
        // If they were directly assigned, the "From" personnel of the VERY FIRST log is the original
        $original_personnel = $logs[0]['from_personnel'];
    } elseif (!empty($appData['current_personnel'])) {
        // If no logs and no request, the current is the original
        $original_personnel = $appData['current_personnel'];
    }
}

echo json_encode([
    'original' => $original_personnel,
    'applied_date' => ($appData && $appData['applied_date']) ? date('M d, Y - h:i A', strtotime($appData['applied_date'])) : 'Unknown Date',
    'logs' => $logs
]);
?>
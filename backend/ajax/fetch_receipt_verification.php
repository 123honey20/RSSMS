<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

// Security
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!isset($_SESSION['service_role']) || 
    strtolower(trim($_SESSION['service_role'])) !== 'grammarly & ai checking') {
    echo json_encode(["error" => "Access Denied"]);
    exit;
}

// Fetch all uploaded receipts

$sql = "
SELECT 
    t.id,
    t.round,
    t.status,
    t.receipt_path,
    t.created_at,
    s.control_number,
    d.name AS department_name
FROM grammarly_ai_transactions t
JOIN students s ON t.student_id = s.id
JOIN departments d ON s.department_id = d.id
WHERE t.receipt_path IS NOT NULL
AND t.receipt_path != ''
ORDER BY t.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data
]);
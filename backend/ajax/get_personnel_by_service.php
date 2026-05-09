<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$service = $_GET['service'] ?? '';
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;

if (empty($service) || $dept_id === 0) {
    echo json_encode([]);
    exit;
}

// Fetch personnel and count their current active workload
// We join with personnel_departments to ensure the personnel belongs to the selected department
$stmt = $conn->prepare("
    SELECT p.id, p.full_name, COUNT(sa.id) as workload
    FROM personnel p
    JOIN users u ON p.user_id = u.id
    JOIN personnel_departments pd ON p.user_id = pd.user_id
    LEFT JOIN service_applications sa ON sa.assigned_personnel_id = p.id AND sa.service_type = ? AND sa.status = 'Approved'
    WHERE u.status = 'Approved' 
      AND p.service_role = ? 
      AND pd.department_id = ?
    GROUP BY p.id
    ORDER BY p.full_name ASC
");
$stmt->bind_param("ssi", $service, $service, $dept_id);
$stmt->execute();
$res = $stmt->get_result();

$personnel = [];
while ($row = $res->fetch_assoc()) {
    $personnel[] = $row;
}
$stmt->close();

echo json_encode($personnel);
?>
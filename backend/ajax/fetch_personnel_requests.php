<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["requests" => []]); exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
// Hardcode the service to only return Statistician records
$service = 'Statistician'; 
$status = $_GET['status'] ?? 'Pending';
$dept = $_GET['dept'] ?? 'All';

$where = "WHERE sa.service_type = 'Statistician'"; // Hardcoded rule
$params = [];
$types = "";

if ($status !== 'All') {
    $where .= " AND sa.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i";
}
if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

$sql = "
    SELECT sa.*, s.control_number, s.department_id, d.name as department_name, p.full_name as requested_name 
    FROM service_applications sa
    JOIN students s ON sa.student_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN personnel p ON sa.requested_personnel_id = p.id
    $where
    ORDER BY sa.created_at ASC
    LIMIT ? OFFSET ?
";

$params[] = $limit; $params[] = $offset; $types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$requests = [];
while ($row = $res->fetch_assoc()) {
    // FIX: Format the Timeline dates
    $row['formatted_created_at'] = !empty($row['created_at']) ? date('M d, Y - h:i A', strtotime($row['created_at'])) : 'Unknown';
    $row['formatted_updated_at'] = !empty($row['updated_at']) ? date('M d, Y - h:i A', strtotime($row['updated_at'])) : '--';
    
    $requests[] = $row;
}

echo json_encode(["requests" => $requests]);
?>
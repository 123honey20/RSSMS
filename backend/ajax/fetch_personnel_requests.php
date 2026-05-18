<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["requests" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0]); exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

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

// 1. GET TOTAL COUNT FOR PAGINATION
$countSql = "
    SELECT COUNT(*) as total 
    FROM service_applications sa
    JOIN students s ON sa.student_id = s.id
    $where
";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();

// 2. FETCH PAGINATED RECORDS (Including Both Requested & Assigned Personnel Names)
$sql = "
    SELECT sa.*, s.control_number, s.department_id, d.name as department_name, 
           p_req.full_name as requested_name,
           p_assign.full_name as assigned_name
    FROM service_applications sa
    JOIN students s ON sa.student_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN personnel p_req ON sa.requested_personnel_id = p_req.id
    LEFT JOIN personnel p_assign ON sa.assigned_personnel_id = p_assign.id
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
    // Format the Timeline dates
    $row['formatted_created_at'] = !empty($row['created_at']) ? date('M d, Y - h:i A', strtotime($row['created_at'])) : 'Unknown';
    $row['formatted_updated_at'] = !empty($row['updated_at']) ? date('M d, Y - h:i A', strtotime($row['updated_at'])) : '--';
    
    $requests[] = $row;
}

// Return the full JSON payload with pagination details
echo json_encode([
    "requests" => $requests,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);
?>
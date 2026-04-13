<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["transactions" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0]); exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}
if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i";
}
if ($status !== 'All' && !empty($status)) {
    $where .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($sy !== 'All' && !empty($sy)) {
    $where .= " AND s.school_year = ?";
    $params[] = $sy;
    $types .= "s";
}

$countSql = "SELECT COUNT(*) as total FROM grammarly_ai_transactions t JOIN students s ON t.student_id = s.id $where";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// FIXED: Added JOIN to service_applications and personnel to fetch 'personnel_name'
$sql = "
    SELECT t.id, t.status, t.round, t.receipt_path, 
           s.control_number, s.research_leader, s.thesis_title, u.school_id, u.email, 
           d.name AS department_name, c.name AS course_name,
           p.full_name AS personnel_name
    FROM grammarly_ai_transactions t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = 'Grammarly & AI Checking' AND sa.status = 'Approved'
    LEFT JOIN personnel p ON sa.assigned_personnel_id = p.id
    $where
    ORDER BY CASE WHEN t.status = 'Receipt Uploaded' THEN 1 ELSE 2 END, t.id DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit; $params[] = $offset; $types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }
echo json_encode(["transactions" => $data, "totalPages" => $totalPages, "currentPage" => $page, "totalRows" => $totalRows]);
?>
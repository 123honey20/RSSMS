<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

// 1. YOUR AWESOME SECURITY CHECKS
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(["data" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0, "error" => "Unauthorized"]);
    exit;
}

if (!isset($_SESSION['service_role']) || strtolower(trim($_SESSION['service_role'])) !== 'grammarly & ai checking') {
    echo json_encode(["data" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0, "error" => "Access Denied"]);
    exit;
}

// 2. PAGINATION & FILTER VARIABLES
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';

// 3. YOUR RECEIPT VALIDATION CHECK
$where = "WHERE t.receipt_path IS NOT NULL AND t.receipt_path != ''";
$params = [];
$types = "";

// Search Filter
if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Department Filter
if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i";
}

// Status Filter
if ($status !== 'All' && !empty($status)) {
    $where .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

// 4. COUNT TOTAL FOR PAGINATION
$countSql = "
    SELECT COUNT(*) as total
    FROM grammarly_ai_transactions t
    JOIN students s ON t.student_id = s.id
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

// 5. FETCH THE ACTUAL RECORDS
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
    $where
    ORDER BY 
        CASE WHEN t.status = 'Receipt Uploaded' THEN 1 ELSE 2 END,
        t.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

echo json_encode([
    "data" => $data,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);
?>
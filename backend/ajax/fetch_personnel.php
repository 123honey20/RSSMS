<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

// Security check (admin only)
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "personnel" => [],
        "totalPages" => 0,
        "currentPage" => 1
    ]);
    exit;
}

// Pagination setup
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';

// Base WHERE conditions
$where = "WHERE u.role = 'personnel'";
$params = [];
$types = "";

// Search filter (school_id)
if (!empty($search)) {
    $where .= " AND u.school_id LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Status filter
if ($status !== 'All' && in_array($status, ['Pending', 'Approved'])) {
    $where .= " AND u.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Count total rows
$countSql = "
    SELECT COUNT(*) as total
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    $where
";

$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}


// Fetch paginated records
$sql = "
    SELECT 
        u.id,
        u.school_id,
        u.email,
        u.status,
        s.full_name,
        s.department_id,
        d.name AS department_name,
        s.service_role
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    $where
    ORDER BY u.status DESC, u.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

// Add limit + offset
$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;

$typesWithLimit = $types . "ii";

$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);

$stmt->execute();
$result = $stmt->get_result();

$personnel = [];

while ($row = $result->fetch_assoc()) {
    $personnel[] = $row;
}

// Output JSON
echo json_encode([
    "personnel" => $personnel,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);

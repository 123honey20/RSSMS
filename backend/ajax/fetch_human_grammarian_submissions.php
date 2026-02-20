<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode([
        "submissions" => [],
        "totalPages" => 0,
        "currentPage" => 1,
        "totalRows" => 0
    ]);
    exit;
}

if (!isset($_SESSION['service_role']) || strtolower(trim($_SESSION['service_role'])) !== 'human grammarian') {
    echo json_encode([
        "submissions" => [],
        "totalPages" => 0,
        "currentPage" => 1,
        "totalRows" => 0
    ]);
    exit;
}

$department_id = intval($_SESSION['department_id']);

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE s.department_id = ?";

$params = [$department_id];
$types = "i";

if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Count total
$countSql = "
    SELECT COUNT(*) as total
    FROM human_grammarian h
    JOIN students s ON h.student_id = s.id
    $where
";

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch records
$sql = "
    SELECT 
        h.id,
        h.status,
        h.round,
        s.control_number,
        s.research_leader,
        u.email,
        d.name AS department_name,
        c.name AS course_name
    FROM human_grammarian h
    JOIN students s ON h.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    $where
    ORDER BY h.id DESC
    LIMIT ? OFFSET ?
";


$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "submissions" => $data,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);

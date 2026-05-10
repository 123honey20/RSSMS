<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["records" => [], "totalPages" => 0, "currentPage" => 1]);
    exit;
}

// 1. SECURE THE TABLE NAME
$allowed_tables = ['grammarly_ai', 'ethics', 'human_grammarian', 'librarian', 'statistician'];
$table = isset($_GET['table']) ? $_GET['table'] : '';

if (!in_array($table, $allowed_tables)) {
    echo json_encode(["error" => "Invalid table requested."]);
    exit;
}

// NEW: Map the table name to the official Service Role name used in service_applications
$serviceMap = [
    'grammarly_ai' => 'Grammarly & AI Checking',
    'ethics' => 'Ethics',
    'human_grammarian' => 'Human Grammarian',
    'librarian' => 'Librarian',
    'statistician' => 'Statistician'
];
$service_type = $serviceMap[$table];

// 2. Variables & Pagination
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';
$dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';

$where = "WHERE 1=1";
$params = [];
$types = "";

// CHANGED: Search by Control Number (searches s.control_number instead of u.school_id)
if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// School Year filter
if ($sy !== 'All' && !empty($sy)) {
    $where .= " AND s.school_year = ?";
    $params[] = $sy;
    $types .= "s";
}

// Department filter
if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i";
}

// Status filter
if ($status !== 'All') {
    $where .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

// 3. Count Total Records
$countSql = "
    SELECT COUNT(*) as total
    FROM `$table` a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
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

// 4. Fetch the Detailed Records
// CHANGED: Replaced u.school_id with s.control_number in the SELECT clause
$sql = "
    SELECT 
        a.id, a.file_path, a.status, a.round,
        s.control_number, 
        s.thesis_title, s.school_year,
        d.name AS department_name, 
        c.name AS course_name,
        p.full_name AS personnel_name
    FROM `$table` a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = '$service_type'
    LEFT JOIN personnel p ON p.id = COALESCE(sa.assigned_personnel_id, a.personnel_id)
    $where
    ORDER BY a.id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;
$typesWithLimit = $types . "ii";

$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}
$stmt->close();

echo json_encode([
    "records" => $records,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);
?>
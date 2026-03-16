<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "students" => [],
        "totalPages" => 0,
        "currentPage" => 1
    ]);
    exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';
// NEW: Capture the school year parameter
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';

$where = "WHERE u.role = 'student'";
$params = [];
$types = "";

// Search by school_id
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

// NEW: School Year filter
if ($sy !== 'All' && !empty($sy)) {
    // We target the 's' (students) table alias since that's where the year belongs
    $where .= " AND s.school_year = ?";
    $params[] = $sy;
    $types .= "s";
}

// Count total
$countSql = "
    SELECT COUNT(*) as total
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    $where
";

$countStmt = $conn->prepare($countSql);

// Only bind params if we actually added any to the array
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();

// Fetch students
$sql = "
    SELECT 
        u.id,
        u.school_id,
        u.email,
        u.status,
        s.thesis_title,
        s.control_number,
        s.research_leader,
        s.school_year, 
        d.name AS department_name,
        c.name AS course_name
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    $where
    ORDER BY u.status DESC, u.created_at DESC
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

$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

echo json_encode([
    "students" => $students,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);
?>
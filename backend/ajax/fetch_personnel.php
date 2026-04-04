<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["personnel" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0]); exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$role = isset($_GET['role']) ? $_GET['role'] : 'All';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';

$where = "WHERE u.role = 'personnel'";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND u.school_id LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}
// NEW: Filter using the Junction Table
if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND EXISTS (SELECT 1 FROM personnel_departments pd WHERE pd.user_id = u.id AND pd.department_id = ?)";
    $params[] = $dept;
    $types .= "i";
}
if ($role !== 'All' && !empty($role)) {
    $where .= " AND s.service_role = ?";
    $params[] = $role;
    $types .= "s";
}
if ($status !== 'All' && in_array($status, ['Pending', 'Approved'])) {
    $where .= " AND u.status = ?";
    $params[] = $status;
    $types .= "s";
}

$countSql = "
    SELECT COUNT(*) as total
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    $where
";

$countStmt = $conn->prepare($countSql);
if (!empty($params)) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
if ($page > $totalPages && $totalPages > 0) { $page = $totalPages; }

// NEW: Use GROUP_CONCAT to get all assigned department names combined into one string
$sql = "
    SELECT 
        u.id, u.school_id, u.email, u.status,
        s.full_name, s.service_role,
        (
            SELECT GROUP_CONCAT(d.name SEPARATOR ', ') 
            FROM personnel_departments pd 
            JOIN departments d ON pd.department_id = d.id 
            WHERE pd.user_id = u.id
        ) AS department_name
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    $where
    ORDER BY CASE WHEN u.status = 'Pending' THEN 1 ELSE 2 END, u.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit; $params[] = $offset; $types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$personnel = [];
while ($row = $result->fetch_assoc()) { $personnel[] = $row; }

echo json_encode([
    "personnel" => $personnel,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);
?>
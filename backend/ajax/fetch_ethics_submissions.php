<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(["submissions" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0]); exit;
}
if (!isset($_SESSION['service_role']) || strtolower(trim($_SESSION['service_role'])) !== 'ethics') {
    echo json_encode(["submissions" => [], "totalPages" => 0, "currentPage" => 1, "totalRows" => 0]); exit;
}

$user_id = $_SESSION['user'];

// 1. Get the actual Personnel ID of the logged-in user
$persStmt = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$persStmt->bind_param("i", $user_id);
$persStmt->execute();
$personnel_res = $persStmt->get_result()->fetch_assoc();
$personnel_id = $personnel_res['id'] ?? 0;
$persStmt->close();

// 2. Get array of all allowed department IDs from junction table
$allowed_depts = [];
$stmtP = $conn->prepare("SELECT department_id FROM personnel_departments WHERE user_id = ?");
$stmtP->bind_param("i", $user_id);
$stmtP->execute();
$resP = $stmtP->get_result();
while($rowP = $resP->fetch_assoc()) { 
    $allowed_depts[] = $rowP['department_id']; 
}
$stmtP->close();

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';
$target_dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';

// 3. CRITICAL UPDATE: Lock query to ONLY show assigned students
$where = "WHERE sa.service_type = 'Ethics' AND sa.status = 'Approved' AND sa.assigned_personnel_id = ?";
$params = [$personnel_id];
$types = "i";

// 4. Department Security Filter
if (empty($allowed_depts)) {
    $where .= " AND s.department_id = 0";
} else {
    if ($target_dept !== 'All' && !empty($target_dept)) {
        if (in_array($target_dept, $allowed_depts)) {
            $where .= " AND s.department_id = ?";
            $params[] = $target_dept;
            $types .= "i";
        } else {
            $where .= " AND s.department_id = 0"; 
        }
    } else {
        $placeholders = implode(',', array_fill(0, count($allowed_depts), '?'));
        $where .= " AND s.department_id IN ($placeholders)";
        foreach ($allowed_depts as $d_id) {
            $params[] = $d_id;
            $types .= "i";
        }
    }
}

if (!empty($search)) {
    $where .= " AND s.control_number LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}
if ($status !== 'All' && !empty($status)) {
    $where .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($sy !== 'All' && !empty($sy)) {
    $where .= " AND s.school_year = ?";
    $params[] = $sy;
    $types .= "s";
}

// Update Count Query
$countSql = "
    SELECT COUNT(*) as total 
    FROM ethics e 
    JOIN students s ON e.student_id = s.id 
    JOIN service_applications sa ON sa.student_id = s.id
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

// Update Main Query
$sql = "
    SELECT e.id, e.status, e.round, s.control_number, s.research_leader, s.thesis_title, u.email, d.name AS department_name, c.name AS course_name
    FROM ethics e
    JOIN students s ON e.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    JOIN service_applications sa ON sa.student_id = s.id
    $where
    ORDER BY CASE WHEN e.status = 'Pending' THEN 1 ELSE 2 END, e.id DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit; $params[] = $offset; $types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }
$stmt->close();

echo json_encode(["submissions" => $data, "totalPages" => $totalPages, "currentPage" => $page, "totalRows" => $totalRows]);
?>
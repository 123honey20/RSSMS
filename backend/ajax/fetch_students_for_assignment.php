<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["students" => [], "error" => "Unauthorized"]); 
    exit;
}

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$service = $_GET['service'] ?? 'Grammarly & AI Checking'; 
$dept = $_GET['dept'] ?? 'All';
$assignment_status = $_GET['assignment_status'] ?? 'All'; 

// FIX: Added Statistician to the allowed array list!
if (!in_array($service, ['Grammarly & AI Checking', 'Librarian', 'Human Grammarian', 'Ethics', 'Statistician'])) {
    $service = 'Grammarly & AI Checking';
}

$where = "WHERE u.status = 'Approved'";
$params = [];
$types = "";

// --- Filter out students whose course does NOT require the selected service ---
$req_column = '';
if ($service === 'Grammarly & AI Checking') {
    $req_column = 'c.req_grammarly_ai';
} elseif ($service === 'Librarian') {
    $req_column = 'c.req_librarian';
} elseif ($service === 'Human Grammarian') {
    $req_column = 'c.req_human_grammarian';
} elseif ($service === 'Ethics') {
    $req_column = 'c.req_ethics';
} elseif ($service === 'Statistician') {
    $req_column = 'c.req_statistician';
}

if (!empty($req_column)) {
    $where .= " AND ($req_column IS NULL OR $req_column = 1)";
}
// --------------------------------------------------------------------------------------

if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i";
}

if (!empty($search)) {
    $where .= " AND (s.control_number LIKE ? OR s.research_leader LIKE ?)";
    $searchWild = "%" . $search . "%";
    array_push($params, $searchWild, $searchWild);
    $types .= "ss";
}

if ($assignment_status === 'Assigned') {
    $where .= " AND sa.assigned_personnel_id IS NOT NULL";
} elseif ($assignment_status === 'Not Assigned') {
    $where .= " AND sa.assigned_personnel_id IS NULL";
}

// 1. COUNT TOTAL RECORDS FOR PAGINATION
$countSql = "
    SELECT COUNT(*) as total
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = ?
    LEFT JOIN personnel p ON sa.assigned_personnel_id = p.id
    $where
";

$countParams = $params;
$countTypes = $types;
array_unshift($countParams, $service);
$countTypes = "s" . $countTypes;

$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$total_records = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_records / $limit);
$countStmt->close();

// 2. FETCH PAGINATED DATA
$sql = "
    SELECT 
        s.id as student_id, 
        s.control_number, 
        s.research_leader,
        s.thesis_title,
        s.department_id, 
        u.email, 
        u.school_id,
        u.status,
        d.name as department_name,
        c.name as course_name,
        sa.assigned_personnel_id, 
        sa.updated_at, sa.created_at, -- FIX: Fetched the timestamps to display!
        p.full_name as assigned_personnel_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = ?
    LEFT JOIN personnel p ON sa.assigned_personnel_id = p.id
    $where
    ORDER BY s.id DESC
    LIMIT ? OFFSET ?
";

$paramsData = $params;
$typesData = $types;
array_unshift($paramsData, $service);
$typesData = "s" . $typesData;

$paramsData[] = $limit; 
$paramsData[] = $offset; 
$typesData .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($paramsData)) {
    $stmt->bind_param($typesData, ...$paramsData);
}
$stmt->execute();
$res = $stmt->get_result();

$students = [];
while ($row = $res->fetch_assoc()) {
    // FIX: Safely format the date depending on whether it was an INSERT or an UPDATE
    $date = $row['updated_at'] ?: $row['created_at'];
    $row['formatted_assigned_date'] = $date ? date('M d, Y - h:i A', strtotime($date)) : '--';
    
    $students[] = $row;
}

echo json_encode([
    "students" => $students, 
    "total_pages" => $total_pages, 
    "current_page" => $page,
    "total_records" => $total_records
]);
?>
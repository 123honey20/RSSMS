<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once "../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// New Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';
$dept = isset($_GET['dept']) ? $_GET['dept'] : 'All';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'Cleared';

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (s.control_number LIKE ? OR s.thesis_title LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if ($sy !== 'All' && !empty($sy)) {
    $where .= " AND s.school_year = ?";
    $params[] = $sy;
    $types .= "s";
}

if ($dept !== 'All' && !empty($dept)) {
    $where .= " AND s.department_id = ?";
    $params[] = $dept;
    $types .= "i"; // integer for department ID
}

// Logic to switch between Cleared and Uncleared
$allApprovedCondition = "
    EXISTS (SELECT 1 FROM grammarly_ai WHERE student_id = s.id AND status = 'Approved') AND
    EXISTS (SELECT 1 FROM ethics WHERE student_id = s.id AND status = 'Approved') AND
    EXISTS (SELECT 1 FROM human_grammarian WHERE student_id = s.id AND status = 'Approved') AND
    EXISTS (SELECT 1 FROM librarian WHERE student_id = s.id AND status = 'Approved') AND
    EXISTS (SELECT 1 FROM statistician WHERE student_id = s.id AND status = 'Approved')
";

if ($statusFilter === 'Cleared') {
    $where .= " AND ($allApprovedCondition)";
} else if ($statusFilter === 'Uncleared') {
    $where .= " AND NOT ($allApprovedCondition)";
}

try {
    // Count Query
    $countQuery = "SELECT COUNT(*) as total FROM students s $where";
    $stmtCount = $conn->prepare($countQuery);
    if (!empty($params)) { $stmtCount->bind_param($types, ...$params); }
    $stmtCount->execute();
    $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = ceil($totalRows / $limit);

    // Main Query: ADDED u.status HERE!
    $query = "
        SELECT 
            s.id, s.control_number, s.thesis_title, s.research_leader, s.school_year, 
            u.school_id, u.email, u.status,
            d.name as department_name, c.name as course_name,
            (SELECT status FROM grammarly_ai WHERE student_id = s.id ORDER BY id DESC LIMIT 1) as grammarly_status,
            (SELECT status FROM ethics WHERE student_id = s.id ORDER BY id DESC LIMIT 1) as ethics_status,
            (SELECT status FROM human_grammarian WHERE student_id = s.id ORDER BY id DESC LIMIT 1) as hg_status,
            (SELECT status FROM librarian WHERE student_id = s.id ORDER BY id DESC LIMIT 1) as librarian_status,
            (SELECT status FROM statistician WHERE student_id = s.id ORDER BY id DESC LIMIT 1) as statistician_status
        FROM students s
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN courses c ON s.course_id = c.id
        $where
        ORDER BY s.id DESC
        LIMIT ?, ?
    ";

    $stmt = $conn->prepare($query);
    $mainParams = $params;
    $mainTypes = $types . "ii";
    $mainParams[] = $offset;
    $mainParams[] = $limit;

    if (!empty($mainParams)) { $stmt->bind_param($mainTypes, ...$mainParams); }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $completed = [];
    while ($row = $result->fetch_assoc()) {
        $completed[] = $row;
    }

    echo json_encode([
        'completed' => $completed,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalRows' => $totalRows
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
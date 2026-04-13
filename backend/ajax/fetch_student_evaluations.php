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

// Grab the new filters from the frontend
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sy = isset($_GET['sy']) ? $_GET['sy'] : 'All';
$service = isset($_GET['service']) ? $_GET['service'] : 'All';

// Build the dynamic WHERE clause
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    // Smart Search: Looks at both Control Number OR Personnel Name
    $where .= " AND (s.control_number LIKE ? OR p.full_name LIKE ?)";
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

if ($service !== 'All' && !empty($service)) {
    $where .= " AND se.service_type = ?";
    $params[] = $service;
    $types .= "s";
}

try {
    // Count Query (FIXED: Changed JOIN to LEFT JOIN so older/unassigned evaluations still show up)
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM student_evaluations se
        JOIN students s ON se.student_id = s.id
        LEFT JOIN personnel p ON se.personnel_id = p.id
        $where
    ";
    
    $stmtCount = $conn->prepare($countQuery);
    if (!$stmtCount) throw new Exception("Count Query Prep Failed: " . $conn->error);
    
    // Bind parameters dynamically if there are any
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    
    $stmtCount->execute();
    $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = ceil($totalRows / $limit);

    // Main Query (FIXED: Changed JOIN to LEFT JOIN so older/unassigned evaluations still show up)
    $query = "
        SELECT 
            se.id, se.total_score, se.comments, se.created_at, se.service_type,
            s.control_number, s.thesis_title, s.research_leader,
            u.school_id, u.email,
            d.name as department_name, c.name as course_name,
            p.full_name as personnel_name, p.service_role,
            r.name as rubric_name
        FROM student_evaluations se
        JOIN students s ON se.student_id = s.id
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN personnel p ON se.personnel_id = p.id
        JOIN rubrics r ON se.rubric_id = r.id
        $where
        ORDER BY se.created_at DESC
        LIMIT ?, ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Main Query Prep Failed: " . $conn->error);

    // Create a new parameter array to include LIMIT and OFFSET at the end
    $mainParams = $params;
    $mainTypes = $types . "ii";
    $mainParams[] = $offset;
    $mainParams[] = $limit;

    if (!empty($mainParams)) {
        $stmt->bind_param($mainTypes, ...$mainParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $evaluations = [];
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }

    echo json_encode([
        'evaluations' => $evaluations,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalRows' => $totalRows
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
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
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count Query
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM student_evaluations se
        JOIN students s ON se.student_id = s.id
        WHERE s.control_number LIKE ?
    ";
    
    $stmtCount = $conn->prepare($countQuery);
    if (!$stmtCount) throw new Exception("Count Query Prep Failed: " . $conn->error);
    
    $stmtCount->bind_param("s", $search);
    $stmtCount->execute();
    $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = ceil($totalRows / $limit);

    // Main Query: We added the `users u` join here to get school_id and email!
    $query = "
        SELECT 
            se.id, se.total_score, se.comments, se.created_at,
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
        JOIN personnel p ON se.personnel_id = p.id
        JOIN rubrics r ON se.rubric_id = r.id
        WHERE s.control_number LIKE ?
        ORDER BY se.created_at DESC
        LIMIT ?, ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Main Query Prep Failed: " . $conn->error);

    $stmt->bind_param("sii", $search, $offset, $limit);
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
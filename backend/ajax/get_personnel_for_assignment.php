<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Security check to ensure only admins can fetch this list
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$dept_id = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$service = isset($_GET['service']) ? $_GET['service'] : '';

$personnel = [];

// Prevent running empty queries
if ($dept_id === 0 || empty($service)) {
    echo json_encode([]);
    exit;
}

// ALL services (including Grammarly) are now bound by the junction table
// We also join the 'users' table to ensure we only fetch 'Approved' personnel
$stmt = $conn->prepare("
    SELECT p.id, p.full_name 
    FROM personnel p
    JOIN users u ON p.user_id = u.id
    JOIN personnel_departments pd ON p.user_id = pd.user_id
    WHERE u.status = 'Approved' 
      AND p.service_role = ? 
      AND pd.department_id = ?
    ORDER BY p.full_name ASC
");
$stmt->bind_param("si", $service, $dept_id);
$stmt->execute();

$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $personnel[] = $row;
}
$stmt->close();

echo json_encode($personnel);
?>
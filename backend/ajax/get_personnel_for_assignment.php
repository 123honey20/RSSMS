<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$dept_id = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$service = isset($_GET['service']) ? $_GET['service'] : '';

$personnel = [];

if ($service === 'Grammarly & AI Checking') {
    // Grammarly personnel are global
    $stmt = $conn->prepare("SELECT id, full_name FROM personnel WHERE service_role = ?");
    $stmt->bind_param("s", $service);
} else {
    // Others are bound by the junction table
    $stmt = $conn->prepare("
        SELECT p.id, p.full_name 
        FROM personnel p
        JOIN personnel_departments pd ON p.user_id = pd.user_id
        WHERE p.service_role = ? AND pd.department_id = ?
    ");
    $stmt->bind_param("si", $service, $dept_id);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $personnel[] = $row;
}
$stmt->close();

echo json_encode($personnel);
?>
<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$service = $_GET['service'] ?? '';
$personnel_id = isset($_GET['personnel_id']) ? (int)$_GET['personnel_id'] : 0;

$table_map = [
    'Grammarly & AI Checking' => 'grammarly_ai',
    'Librarian' => 'librarian',
    'Human Grammarian' => 'human_grammarian',
    'Ethics' => 'ethics',
    'Statistician' => 'statistician'
];

$table = $table_map[$service] ?? null;

if (!$table || $personnel_id === 0) {
    echo json_encode([]);
    exit;
}

// FIX: Grammarly & AI Checking doesn't have a 'phase' column. 
// We dynamically build the phase part of the query to prevent SQL crashes.
$phase_select = ($table === 'grammarly_ai') 
    ? "1 as latest_phase" 
    : "(SELECT IFNULL(sub.phase, 1) FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_phase";

// Fetch students assigned to this personnel, including course requirements and latest status
$sql = "
    SELECT s.id as student_id, s.control_number, s.research_leader, d.name as dept_name, c.name as course_name,
           $phase_select,
           (SELECT IFNULL(sub.round, 1) FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_round,
           (SELECT IFNULL(sub.status, '') FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_status,
           (SELECT sub.id FROM $table sub WHERE sub.student_id = s.id LIMIT 1) as has_submission,
           COALESCE(csr.required_phases, 1) as max_phases
    FROM students s
    JOIN service_applications sa ON sa.student_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN course_service_requirements csr ON csr.course_id = s.course_id AND csr.service_type = ?
    WHERE sa.service_type = ? AND sa.assigned_personnel_id = ? AND sa.status = 'Approved'
    ORDER BY s.control_number ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $service, $service, $personnel_id);
$stmt->execute();
$res = $stmt->get_result();

$students = [];
while ($row = $res->fetch_assoc()) {
    // Formatting fallback if they have no submissions yet
    if (!$row['has_submission']) {
        $row['current_progress'] = "No submissions yet";
    } else {
        // CHECK IF FULLY COMPLETED!
        if ($row['latest_status'] === 'Approved' && (int)$row['latest_phase'] >= (int)$row['max_phases']) {
            $row['current_progress'] = "Completed";
        } else {
            if ($service === 'Grammarly & AI Checking' || (int)$row['max_phases'] <= 1) {
                $row['current_progress'] = "Round " . $row['latest_round'];
            } else {
                $row['current_progress'] = "Phase " . $row['latest_phase'] . ", Round " . $row['latest_round'];
            }
        }
    }
    
    $students[] = $row;
}
$stmt->close();

echo json_encode($students);
?>
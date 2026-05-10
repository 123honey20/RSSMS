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

// Bypass the & symbol bug using LIKE for the main queries
$search_term = "%" . $service . "%";
if ($service === 'Grammarly & AI Checking') {
    $search_term = "%Grammarly%";
} elseif ($service === 'Human Grammarian') {
    $search_term = "%Human Grammarian%";
}

// Dynamically build the phase part of the query
$phase_select = ($table === 'grammarly_ai') 
    ? "1 as latest_phase" 
    : "(SELECT IFNULL(sub.phase, 1) FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_phase";

// Fetch students assigned to this personnel, including the ORIGINAL personnel ID from the logs!
$sql = "
    SELECT s.id as student_id, s.control_number, s.research_leader, d.name as dept_name, c.name as course_name,
           $phase_select,
           (SELECT IFNULL(sub.round, 1) FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_round,
           (SELECT IFNULL(sub.status, '') FROM $table sub WHERE sub.student_id = s.id ORDER BY sub.id DESC LIMIT 1) as latest_status,
           (SELECT sub.id FROM $table sub WHERE sub.student_id = s.id LIMIT 1) as has_submission,
           COALESCE(csr.required_phases, 1) as max_phases,
           (SELECT rl.from_personnel_id FROM reassignment_logs rl WHERE rl.student_id = s.id AND rl.service_type = ? ORDER BY rl.reassigned_at ASC LIMIT 1) as original_personnel_id,
           (SELECT p_orig.full_name FROM reassignment_logs rl2 JOIN personnel p_orig ON rl2.from_personnel_id = p_orig.id WHERE rl2.student_id = s.id AND rl2.service_type = ? ORDER BY rl2.reassigned_at ASC LIMIT 1) as original_personnel_name
    FROM students s
    JOIN service_applications sa ON sa.student_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN course_service_requirements csr ON csr.course_id = s.course_id AND csr.service_type LIKE ?
    WHERE sa.service_type LIKE ? AND sa.assigned_personnel_id = ? AND sa.status = 'Approved'
    ORDER BY s.control_number ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $service, $service, $search_term, $search_term, $personnel_id);
$stmt->execute();
$res = $stmt->get_result();

$students = [];
while ($row = $res->fetch_assoc()) {
    $row['is_completed'] = false; // Default flag

    // Formatting fallback if they have no submissions yet
    if (!$row['has_submission']) {
        $row['current_progress'] = "No submissions yet";
    } else {
        // CHECK IF FULLY COMPLETED!
        if ($row['latest_status'] === 'Approved' && (int)$row['latest_phase'] >= (int)$row['max_phases']) {
            $row['current_progress'] = "Completed";
            $row['is_completed'] = true; // Set flag to lock reassignment
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
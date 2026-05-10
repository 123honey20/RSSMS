<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;

if ($dept_id === 0) {
    echo json_encode([]);
    exit;
}

// 1. Get active school year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';
$safe_sy = $conn->real_escape_string($active_sy);

$personnel_data = [];

// BULLETPROOF FIX: Check BOTH the primary department column AND the junction table simultaneously!
$pStmt = $conn->prepare("
    SELECT p.id, p.full_name, u.school_id 
    FROM personnel p
    JOIN users u ON p.user_id = u.id
    WHERE p.service_role = 'Grammarly & AI Checking' 
      AND (p.department_id = ? OR EXISTS (SELECT 1 FROM personnel_departments pd WHERE pd.user_id = u.id AND pd.department_id = ?))
      AND u.status = 'Approved'
    ORDER BY p.full_name ASC
");
$pStmt->bind_param("ii", $dept_id, $dept_id);
$pStmt->execute();
$pRes = $pStmt->get_result();

while ($person = $pRes->fetch_assoc()) {
    $p_id = $person['id'];

    // Metric A: Total Students Assigned in current SY
    $assignQuery = $conn->query("
        SELECT COUNT(*) as total
        FROM service_applications sa
        JOIN students s ON sa.student_id = s.id
        WHERE sa.assigned_personnel_id = $p_id AND sa.service_type = 'Grammarly & AI Checking' AND s.school_year = '$safe_sy'
    ");
    $total_assigned = $assignQuery->fetch_assoc()['total'] ?? 0;

    // Metric A.2: Fetch actual list of assigned students WITH ASSIGNMENT DATE, ORIGINAL FLAG & CURRENT PROGRESS STATUS
    // FIX: Checks the first log in history to see if the current personnel is truly the original!
    $studentsQuery = $conn->query("
        SELECT s.control_number, s.research_leader, s.thesis_title,
               (sa.assigned_personnel_id != COALESCE(
                   (SELECT rl.from_personnel_id FROM reassignment_logs rl WHERE rl.student_id = s.id AND rl.service_type = 'Grammarly & AI Checking' ORDER BY rl.reassigned_at ASC LIMIT 1),
                   sa.assigned_personnel_id
               )) as is_sub,
               COALESCE(
                   (SELECT MAX(rl.reassigned_at) FROM reassignment_logs rl WHERE rl.student_id = s.id AND rl.service_type = 'Grammarly & AI Checking' AND rl.to_personnel_id = sa.assigned_personnel_id),
                   sa.updated_at,
                   sa.created_at
               ) as assigned_date,
               COALESCE(g.status, 'Pending') as current_status
        FROM service_applications sa
        JOIN students s ON sa.student_id = s.id
        LEFT JOIN (
            SELECT student_id, MAX(id) as max_id 
            FROM grammarly_ai 
            GROUP BY student_id
        ) latest ON latest.student_id = s.id
        LEFT JOIN grammarly_ai g ON g.id = latest.max_id
        WHERE sa.assigned_personnel_id = $p_id AND sa.service_type = 'Grammarly & AI Checking' AND s.school_year = '$safe_sy'
        ORDER BY assigned_date DESC
    ");
    
    $assigned_students = [];
    $recent_assignments = [];
    $count = 0;
    
    while ($stu = $studentsQuery->fetch_assoc()) {
        $stu['formatted_date'] = date('M d, Y - h:i A', strtotime($stu['assigned_date']));
        $assigned_students[] = $stu;
        
        // Grab the 3 most recent assignments to show quickly on the modal
        if ($count < 3) {
            $recent_assignments[] = $stu;
            $count++;
        }
    }

    // Metric B: Document Status Breakdown (NO PHASE CHECK FOR GRAMMARLY)
    $statusQuery = $conn->query("
        SELECT 
            e.status, 
            COUNT(*) as status_count
        FROM grammarly_ai e
        JOIN (
            SELECT student_id, MAX(id) as max_id
            FROM grammarly_ai
            GROUP BY student_id
        ) latest ON e.id = latest.max_id
        JOIN service_applications sa ON sa.student_id = e.student_id
        JOIN students s ON e.student_id = s.id
        WHERE sa.assigned_personnel_id = $p_id AND sa.service_type = 'Grammarly & AI Checking' AND s.school_year = '$safe_sy'
        GROUP BY e.status
    ");

    $pending = 0;
    $revision = 0;
    $approved = 0;

    while ($st = $statusQuery->fetch_assoc()) {
        $count = (int)$st['status_count'];
        if ($st['status'] === 'Pending') {
            $pending += $count;
        } elseif ($st['status'] === 'Needs Revision') {
            $revision += $count;
        } elseif ($st['status'] === 'Approved') {
            $approved += $count; 
        }
    }

    // Metric C: Receipts Pending
    $receiptQuery = $conn->query("
        SELECT COUNT(*) as total_pending_receipts
        FROM grammarly_ai_transactions t
        JOIN service_applications sa ON sa.student_id = t.student_id
        JOIN students s ON t.student_id = s.id
        WHERE sa.assigned_personnel_id = $p_id 
          AND sa.service_type = 'Grammarly & AI Checking' 
          AND s.school_year = '$safe_sy' 
          AND t.status = 'Receipt Uploaded'
    ");
    $total_pending_receipts = $receiptQuery->fetch_assoc()['total_pending_receipts'] ?? 0;

    // Combine data (Rating completely removed)
    $person['total_assigned'] = $total_assigned;
    $person['assigned_students'] = $assigned_students;
    $person['recent_assignments'] = $recent_assignments;
    $person['total_pending'] = $pending;
    $person['total_revision'] = $revision;
    $person['total_approved'] = $approved;
    $person['total_pending_receipts'] = $total_pending_receipts;

    $personnel_data[] = $person;
}

$pStmt->close();
echo json_encode($personnel_data);
?>
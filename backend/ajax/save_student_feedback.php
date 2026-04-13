<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// 1. Validate incoming data structure, including service_type and ratings
if (!$data || empty($data['rubric_id']) || empty($data['submission_id']) || empty($data['service_type']) || empty($data['ratings']) || !is_array($data['ratings'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or incomplete data provided.']);
    exit;
}

$user_id = $_SESSION['user'];

// 2. Safely get the actual student ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
    exit;
}
$student_id = $result['id'];

// 3. Extract variables safely
$personnel_id = (!empty($data['personnel_id']) && $data['personnel_id'] !== 'null') ? intval($data['personnel_id']) : null;
$submission_id = intval($data['submission_id']);
$service_type = htmlspecialchars(strip_tags($data['service_type'])); // Sanitize the string
$rubric_id = intval($data['rubric_id']);
$comments = $data['comments'] ?? '';
$ratings = $data['ratings'];

// Calculate Total Score safely
$total_score = array_sum($ratings);

// 4. Check if this exact evaluation already exists to prevent double-submitting
$stmtCheck = $conn->prepare("SELECT id FROM student_evaluations WHERE student_id = ? AND submission_id = ? AND service_type = ? AND rubric_id = ?");
$stmtCheck->bind_param("iisi", $student_id, $submission_id, $service_type, $rubric_id);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted this evaluation.']);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

// 5. If it doesn't exist, safely begin the database transaction and insert
try {
    $conn->begin_transaction();

    $stmtEval = $conn->prepare("INSERT INTO student_evaluations (student_id, personnel_id, submission_id, service_type, rubric_id, total_score, comments) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtEval->bind_param("iiisiis", $student_id, $personnel_id, $submission_id, $service_type, $rubric_id, $total_score, $comments);
    $stmtEval->execute();

    $eval_id = $conn->insert_id;
    $stmtEval->close();

    $stmtRating = $conn->prepare("INSERT INTO student_evaluation_ratings (evaluation_id, criterion_id, score) VALUES (?, ?, ?)");
    foreach ($ratings as $crit_id => $score) {
        $c_id = intval($crit_id);
        $s_val = intval($score);
        $stmtRating->bind_param("iii", $eval_id, $c_id, $s_val);
        $stmtRating->execute();
    }
    $stmtRating->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Feedback saved successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
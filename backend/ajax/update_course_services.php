<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$course_id = intval($data['course_id'] ?? 0);
$req_grammarly_ai = intval($data['req_grammarly_ai'] ?? 1);
$req_ethics = intval($data['req_ethics'] ?? 1);
$req_human_grammarian = intval($data['req_human_grammarian'] ?? 1);
$req_librarian = intval($data['req_librarian'] ?? 1);
$req_statistician = intval($data['req_statistician'] ?? 1);

if ($course_id > 0) {
    $stmt = $conn->prepare("UPDATE courses SET req_grammarly_ai = ?, req_ethics = ?, req_human_grammarian = ?, req_librarian = ?, req_statistician = ? WHERE id = ?");
    $stmt->bind_param("iiiiii", $req_grammarly_ai, $req_ethics, $req_human_grammarian, $req_librarian, $req_statistician, $course_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update database."]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid course ID."]);
}
?>
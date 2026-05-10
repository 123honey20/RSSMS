<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id > 0) {
    /* 
    IMPORTANT: You must run an SQL ALTER statement on your 'courses' table to add these columns:
    ALTER TABLE courses 
    ADD req_grammarly_ai TINYINT(1) DEFAULT 1,
    ADD req_ethics TINYINT(1) DEFAULT 1,
    ADD req_human_grammarian TINYINT(1) DEFAULT 1,
    ADD req_librarian TINYINT(1) DEFAULT 1,
    ADD req_statistician TINYINT(1) DEFAULT 1;
    */
    $stmt = $conn->prepare("SELECT req_grammarly_ai, req_ethics, req_human_grammarian, req_librarian, req_statistician FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
        exit;
    }
}

echo json_encode(["error" => "Course not found"]);
?>
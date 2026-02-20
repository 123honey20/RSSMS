<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$ethicsId = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT page_number, paragraph_number, comment_text, created_at
    FROM ethics_comments
    WHERE ethics_id = ?
    ORDER BY created_at ASC
");

$stmt->bind_param("i", $ethicsId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];

while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode($comments);

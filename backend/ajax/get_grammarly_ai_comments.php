<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$grammarlyAiId = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT comment_text, created_at
    FROM grammarly_ai_comments
    WHERE grammarly_ai_id = ?
    ORDER BY created_at ASC
");

$stmt->bind_param("i", $grammarlyAiId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];

while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode($comments);

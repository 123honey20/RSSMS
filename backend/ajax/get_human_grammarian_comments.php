<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$humanGrammarianId = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT page_number, paragraph_number, comment_text, created_at
    FROM human_grammarian_comments
    WHERE human_grammarian_id = ?
    ORDER BY created_at ASC
");

$stmt->bind_param("i", $humanGrammarianId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];

while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode($comments);

<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$grammarlyAiId = intval($data['grammarly_ai_id'] ?? 0);
$commentText = trim($data['comment_text'] ?? '');

if (!$grammarlyAiId || empty($commentText)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO grammarly_ai_comments (grammarly_ai_id, comment_text)
    VALUES (?, ?)
");

$stmt->bind_param("is", $grammarlyAiId, $commentText);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

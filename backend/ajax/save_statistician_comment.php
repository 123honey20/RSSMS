<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$statisticianId = intval($data['statistician_id'] ?? 0);
$pageNumber = intval($data['page_number'] ?? 0);
$paragraphNumber = intval($data['paragraph_number'] ?? 0);
$commentText = trim($data['comment_text'] ?? '');

if (!$statisticianId || !$pageNumber || !$paragraphNumber || empty($commentText)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO statistician_comments (statistician_id, page_number, paragraph_number, comment_text)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("iiis", $statisticianId, $pageNumber, $paragraphNumber, $commentText);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE statistician SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

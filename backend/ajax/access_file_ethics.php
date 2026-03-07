<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'personnel') {
    echo json_encode(['success' => false]);
    exit;
}

// 1. Get the logged-in user's ID
$user_id = $_SESSION['user']; 

// 2. Fetch the actual personnel ID from the personnel table
$p_stmt = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$personnel = $p_stmt->get_result()->fetch_assoc();

if (!$personnel) {
    echo json_encode(['success' => false, 'message' => 'Personnel not found']);
    exit;
}

$personnel_id = $personnel['id'];

// 3. Get the incoming data
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false]);
    exit;
}

// 4. Update the ethics table with BOTH the new status and the personnel_id
$stmt = $conn->prepare("UPDATE ethics SET status = ?, personnel_id = ? WHERE id = ?");
$stmt->bind_param("sii", $status, $personnel_id, $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

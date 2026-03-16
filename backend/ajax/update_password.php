<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['current_password'], $data['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}

$current_password = $data['current_password'];
$new_password = $data['new_password'];

// 1. Verify current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

$user = $res->fetch_assoc();
$stmt->close();

if (!password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
    exit();
}

// 2. Hash and update new password
$hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateStmt->bind_param("si", $hashed_new_password, $user_id);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
}

$updateStmt->close();
?>
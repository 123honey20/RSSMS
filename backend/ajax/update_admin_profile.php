<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['school_id'], $data['email']) || empty(trim($data['school_id'])) || empty(trim($data['email']))) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

$school_id = trim($data['school_id']);
$email = trim($data['email']);

// 1. Check if the new school_id or email is already taken by ANOTHER user
$checkStmt = $conn->prepare("SELECT id FROM users WHERE (school_id = ? OR email = ?) AND id != ?");
$checkStmt->bind_param("ssi", $school_id, $email, $user_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username or Email is already taken.']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();

// 2. Update the admin profile
$updateStmt = $conn->prepare("UPDATE users SET school_id = ?, email = ? WHERE id = ?");
$updateStmt->bind_param("ssi", $school_id, $email, $user_id);

if ($updateStmt->execute()) {
    // Update the session variable so the header top-bar updates on refresh!
    $_SESSION['school_id'] = $school_id; 
    echo json_encode(['success' => true, 'message' => 'Admin credentials saved!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

$updateStmt->close();
?>
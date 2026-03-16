<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['role'], $data['email']) || empty(trim($data['email']))) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}

$role = $data['role'];
$email = trim($data['email']);

// Start Database Transaction
$conn->begin_transaction();

try {
    // 1. Update the generic Users table (Email)
    $stmtUsers = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    if (!$stmtUsers) throw new Exception("Users Table Error: " . $conn->error);
    
    $stmtUsers->bind_param("si", $email, $user_id);
    $stmtUsers->execute();
    $stmtUsers->close();

    // 2. Update the specific Role table
    if ($role === 'student') {
        $leader = trim($data['research_leader'] ?? '');
        $control = trim($data['control_number'] ?? '');
        $thesis = trim($data['thesis_title'] ?? '');

        $stmtStudent = $conn->prepare("UPDATE students SET research_leader = ?, control_number = ?, thesis_title = ? WHERE user_id = ?");
        if (!$stmtStudent) throw new Exception("Students Table Error: " . $conn->error);
        
        $stmtStudent->bind_param("sssi", $leader, $control, $thesis, $user_id);
        $stmtStudent->execute();
        $stmtStudent->close();

    } elseif ($role === 'personnel') {
        $name = trim($data['full_name'] ?? '');

        $stmtPersonnel = $conn->prepare("UPDATE personnel SET full_name = ? WHERE user_id = ?");
        if (!$stmtPersonnel) throw new Exception("Personnel Table Error: " . $conn->error);
        
        $stmtPersonnel->bind_param("si", $name, $user_id);
        $stmtPersonnel->execute();
        $stmtPersonnel->close();
    } else {
        throw new Exception("Invalid role sent to server.");
    }

    // If everything succeeds, commit!
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

} catch (Throwable $e) {
    // If ANYTHING fails, rollback to protect data and output the exact error!
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
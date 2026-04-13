<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user'];

// CRITICAL SECURITY FIX: Get the role securely from the server session, NEVER from the frontend payload!
$role = $_SESSION['role']; 

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || empty(trim($data['email']))) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}

$email = trim($data['email']);

// SECURITY ADDITION: Validate the email format before saving it to the database
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit();
}

// NEW FIX: Check if the email already exists for a DIFFERENT user
$stmtCheckEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmtCheckEmail->bind_param("si", $email, $user_id);
$stmtCheckEmail->execute();
if ($stmtCheckEmail->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email address is already in use by another account.']);
    $stmtCheckEmail->close();
    exit();
}
$stmtCheckEmail->close();


// NEW FIX: If role is student, check if Control Number already exists for a DIFFERENT student
if ($role === 'student') {
    $control = trim($data['control_number'] ?? '');
    
    if (!empty($control)) {
        $stmtCheckControl = $conn->prepare("SELECT id FROM students WHERE control_number = ? AND user_id != ?");
        $stmtCheckControl->bind_param("si", $control, $user_id);
        $stmtCheckControl->execute();
        
        if ($stmtCheckControl->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'This Control Number is already registered to another student.']);
            $stmtCheckControl->close();
            exit();
        }
        $stmtCheckControl->close();
    }
}

// Start Database Transaction
$conn->begin_transaction();

try {
    // 1. Update the generic Users table (Email)
    $stmtUsers = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    if (!$stmtUsers) throw new Exception("Users Table Error: " . $conn->error);
    
    $stmtUsers->bind_param("si", $email, $user_id);
    $stmtUsers->execute();
    $stmtUsers->close();

    // 2. Update the specific Role table based on the SECURE SESSION ROLE
    if ($role === 'student') {
        $leader = trim($data['research_leader'] ?? '');
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
        throw new Exception("Invalid user role.");
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
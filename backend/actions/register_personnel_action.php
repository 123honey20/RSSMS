<?php
require_once "../config/database.php";

$school_id  = $_POST['school_id'];
$full_name  = $_POST['full_name'];
$email      = $_POST['email'];
$password   = $_POST['password'];
$department_id = isset($_POST['personnel_department_id']) && $_POST['personnel_department_id'] !== ''
    ? intval($_POST['personnel_department_id'])
    : NULL;
$role       = $_POST['role'];

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table (role = personnel, status = Pending)
$stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, 'personnel', 'Pending')");
$stmt->bind_param("sss", $school_id, $email, $hashedPassword);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;

    // Insert into personnel table
    $stmt2 = $conn->prepare("INSERT INTO personnel (user_id, full_name, department_id, service_role) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("isis", $user_id, $full_name, $department_id, $role);
    $stmt2->execute();

    header("Location: ../../frontend/auth/login.php?success=registered");
    exit();
} else {
    echo "Error: " . $conn->error;
}

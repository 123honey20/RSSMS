<?php
require_once "../config/database.php";

$school_id  = trim($_POST['school_id']);
$full_name  = trim($_POST['full_name']);
$email      = trim($_POST['email']);
$password   = $_POST['password'];
$role       = trim($_POST['role']);

// NEW: Capture the array of selected departments
$departments = isset($_POST['personnel_departments']) ? $_POST['personnel_departments'] : [];

// Fallback for the original column to keep the database happy
$primary_dept = ($role !== 'Grammarly & AI Checking' && !empty($departments)) ? intval($departments[0]) : NULL;

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table (role = personnel, status = Pending)
$stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, 'personnel', 'Pending')");
$stmt->bind_param("sss", $school_id, $email, $hashedPassword);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;

    // Insert into personnel table
    $stmt2 = $conn->prepare("INSERT INTO personnel (user_id, full_name, department_id, service_role) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("isis", $user_id, $full_name, $primary_dept, $role);
    $stmt2->execute();

    // NEW: Insert all selected departments into the Junction Table
    if ($role !== 'Grammarly & AI Checking' && !empty($departments)) {
        $stmt3 = $conn->prepare("INSERT INTO personnel_departments (user_id, department_id) VALUES (?, ?)");
        foreach ($departments as $d_id) {
            $d_id = intval($d_id);
            $stmt3->bind_param("ii", $user_id, $d_id);
            $stmt3->execute();
        }
        $stmt3->close();
    }

    header("Location: ../../frontend/auth/login.php?success=registered");
    exit();
} else {
    echo "Error: " . $conn->error;
}
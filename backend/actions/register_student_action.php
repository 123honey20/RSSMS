<?php

require_once "../config/database.php";


$school_id       = trim($_POST['school_id']);
$email           = trim($_POST['email']);
$password        = $_POST['password'];
$thesis_title    = trim($_POST['thesis_title']);
$control_number  = trim($_POST['control_number']);
$research_leader = trim($_POST['research_leader']);

$department_id = intval($_POST['student_department_id']);
$course_id     = intval($_POST['course_id']);  

if ($department_id <= 0) {
    die("Error: Please select a valid Department.");
}

if ($course_id <= 0) {
    die("Error: Please select a valid Course.");
}


// Hash password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, 'student', 'Pending')");
$stmt->bind_param("sss", $school_id, $email, $hashedPassword);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    // Insert into students table
    $stmt2 = $conn->prepare("
    INSERT INTO students (user_id, thesis_title, control_number, research_leader, department_id, course_id)
    VALUES (?, ?, ?, ?, ?, ?)
");
    $stmt2->bind_param("isssii", $user_id, $thesis_title, $control_number, $research_leader, $department_id, $course_id);


    if ($stmt2->execute()) {
        header("Location: ../../frontend/auth/login.php?success=registered");
        exit();
    } else {
        die("Failed to insert student profile: " . $stmt2->error);
    }
} else {
    die("Failed to create user account: " . $stmt->error);
}

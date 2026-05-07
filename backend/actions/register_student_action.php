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

$school_year   = trim($_POST['school_year'] ?? '2025-2026'); 

if ($department_id <= 0) {
    die("Error: Please select a valid Department.");
}

if ($course_id <= 0) {
    die("Error: Please select a valid Course.");
}

// 1. PRE-CHECK: Ensure ID Number and Email are unique
$check_stmt = $conn->prepare("SELECT school_id, email FROM users WHERE school_id = ? OR email = ? LIMIT 1");
$check_stmt->bind_param("ss", $school_id, $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($row = $check_result->fetch_assoc()) {
    if (strtolower($row['email']) === strtolower($email)) {
        header("Location: ../../frontend/auth/register.php?error=duplicate_email");
        exit();
    }
    if (strtolower($row['school_id']) === strtolower($school_id)) {
        header("Location: ../../frontend/auth/register.php?error=duplicate_id");
        exit();
    }
}
$check_stmt->close();

// 2. PRE-CHECK: Ensure Control Number is unique (No two groups can have the same Control No.)
$check_ctrl = $conn->prepare("SELECT control_number FROM students WHERE control_number = ? LIMIT 1");
$check_ctrl->bind_param("s", $control_number);
$check_ctrl->execute();
if ($check_ctrl->get_result()->fetch_assoc()) {
    header("Location: ../../frontend/auth/register.php?error=duplicate_control_number");
    exit();
}
$check_ctrl->close();


// Hash password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, 'student', 'Pending')");
$stmt->bind_param("sss", $school_id, $email, $hashedPassword);

try {
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        $stmt2 = $conn->prepare("
            INSERT INTO students (user_id, thesis_title, control_number, research_leader, department_id, course_id, school_year)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param("isssiis", $user_id, $thesis_title, $control_number, $research_leader, $department_id, $course_id, $school_year);

        if ($stmt2->execute()) {
            header("Location: ../../frontend/auth/login.php?success=registered");
            exit();
        } else {
            die("Failed to insert student profile: " . $stmt2->error);
        }
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        header("Location: ../../frontend/auth/register.php?error=duplicate_id");
        exit();
    } else {
        die("Failed to create user account: " . $e->getMessage());
    }
}
?>
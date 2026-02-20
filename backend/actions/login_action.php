<?php
session_start();
require_once "../config/database.php";

$school_id = $_POST['school_id'];
$password  = $_POST['password'];

// Find user by school_id only
$stmt = $conn->prepare("SELECT * FROM users WHERE school_id = ?");
$stmt->bind_param("s", $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        header("Location: ../../frontend/auth/login.php?error=invalid");
        exit();
    }

    // Check status (except admin)
    if ($user['role'] !== 'admin' && $user['status'] !== 'Approved') {
        header("Location: ../../frontend/auth/login.php?error=Pending");
        exit();
    }

    // Save session
    $_SESSION['user'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['school_id'] = $user['school_id'];

    // If personnel, get service_role
    if ($user['role'] === 'personnel') {
        $stmt2 = $conn->prepare("SELECT service_role FROM personnel WHERE user_id = ?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        if ($res2->num_rows === 1) {
            $personnel = $res2->fetch_assoc();
            $_SESSION['service_role'] = strtolower(trim($personnel['service_role']));
        } else {
            $_SESSION['service_role'] = null;
        }
    }

    // Redirect by role
    if ($user['role'] === 'student') {
        header("Location: ../../frontend/dashboards/student_dashboard.php");
    } elseif ($user['role'] === 'personnel') {
        header("Location: ../../frontend/dashboards/personnel_dashboard.php");
    } elseif ($user['role'] === 'admin') {
        header("Location: ../../frontend/dashboards/admin_dashboard.php");
    }
    exit();

} else {
    header("Location: ../../frontend/auth/login.php?error=invalid");
    exit();
}

<?php
require_once "../config/database.php";

$school_id  = trim($_POST['school_id']);
$full_name  = trim($_POST['full_name']);
$email      = trim($_POST['email']);
$password   = $_POST['password'];
$role       = trim($_POST['role']);

$departments = isset($_POST['personnel_departments']) ? $_POST['personnel_departments'] : [];
$primary_dept = (!empty($departments)) ? intval($departments[0]) : NULL;

// 1. PRE-CHECK: Ensure ID Number and Email are unique before doing anything else
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


$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table (role = personnel, status = Pending)
$stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, 'personnel', 'Pending')");
$stmt->bind_param("sss", $school_id, $email, $hashedPassword);

try {
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Insert into personnel table
        $stmt2 = $conn->prepare("INSERT INTO personnel (user_id, full_name, department_id, service_role) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isis", $user_id, $full_name, $primary_dept, $role);
        $stmt2->execute();

        // Insert all selected departments into the Junction Table
        if (!empty($departments)) {
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
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        header("Location: ../../frontend/auth/register.php?error=duplicate_id");
        exit();
    } else {
        die("Error: " . $e->getMessage());
    }
}
?>
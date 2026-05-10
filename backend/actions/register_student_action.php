<?php
require_once "../config/database.php";

// Require PHPMailer
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
            
            // ============================================
            // SEND EMAIL NOTIFICATIONS
            // ============================================
            try {
                // Fetch Admin Email dynamically
                $adminQuery = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
                $adminEmail = $adminQuery->fetch_assoc()['email'] ?? 'joshuaalmodiel119@gmail.com';

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'joshuaalmodiel119@gmail.com'; // System Email
                $mail->Password   = 'nprf grsd yrxt auyz'; // App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('joshuaalmodiel119@gmail.com', 'RSSMS System');
                $mail->isHTML(true);

                $header = "<div style='background-color:#f8fafc;padding:20px;font-family:sans-serif;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.05);'>";
                $footer = "<div style='background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;'><p style='margin:0;'>Automated Registration Notice.</p></div></div></div>";

                // --- 1. Email the Student ---
                $mail->addAddress($email, $research_leader);
                $mail->Subject = "Registration Received - Account Pending";
                $mail->Body = $header . "
                    <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Registration Successful</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello <strong>{$research_leader}</strong>,</p>
                        <p>Welcome to the <strong>Research Support Services Monitoring System (RSSMS)</strong>.</p>
                        <p>Your student account has been successfully created. However, it is currently <strong style='color:#d97706;'>Pending Verification</strong> by the System Administrator.</p>
                        <p>You will receive an email notification once your account has been approved and activated. Thank you for your patience!</p>
                    </div>" . $footer;
                $mail->send();

                // --- 2. Email the Admin ---
                $mail->clearAddresses();
                $mail->addAddress($adminEmail, 'System Administrator');
                $mail->Subject = "ACTION REQUIRED: New Student Registration";
                $mail->Body = $header . "
                    <div style='background:#d97706;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>New Student Account</h1></div>
                    <div style='padding:30px;line-height:1.6;color:#334155;'>
                        <p>Hello Admin,</p>
                        <p>A new student group has registered and requires your approval.</p>
                        <div style='background:#f8fafc; border-left: 4px solid #d97706; padding: 15px; margin: 20px 0;'>
                            <p style='margin:0 0 5px 0;'><strong>Control No:</strong> {$control_number}</p>
                            <p style='margin:0 0 5px 0;'><strong>Leader:</strong> {$research_leader}</p>
                            <p style='margin:0;'><strong>Email:</strong> {$email}</p>
                        </div>
                        <p>Please log in to the Admin Dashboard to review and approve this account.</p>
                    </div>" . $footer;
                $mail->send();

            } catch (Exception $e) {
                error_log("PHPMailer Error during student registration: " . $e->getMessage());
            }

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
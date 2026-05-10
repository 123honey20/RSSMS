<?php
require_once "../config/database.php";

// Require PHPMailer
require '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

            // --- 1. Email the Personnel ---
            $mail->addAddress($email, $full_name);
            $mail->Subject = "Registration Received - Account Pending";
            $mail->Body = $header . "
                <div style='background:#2563eb;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>Registration Successful</h1></div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>Hello <strong>{$full_name}</strong>,</p>
                    <p>Welcome to the <strong>Research Support Services Monitoring System (RSSMS)</strong>.</p>
                    <p>Your Personnel account for the role of <strong>{$role}</strong> has been successfully created. However, it is currently <strong style='color:#d97706;'>Pending Verification</strong> by the System Administrator.</p>
                    <p>You will receive an email notification once your account has been approved and activated.</p>
                </div>" . $footer;
            $mail->send();

            // --- 2. Email the Admin ---
            $mail->clearAddresses();
            $mail->addAddress($adminEmail, 'System Administrator');
            $mail->Subject = "ACTION REQUIRED: New Personnel Registration";
            $mail->Body = $header . "
                <div style='background:#d97706;padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:22px;'>New Personnel Account</h1></div>
                <div style='padding:30px;line-height:1.6;color:#334155;'>
                    <p>Hello Admin,</p>
                    <p>A new research personnel has registered and requires your approval.</p>
                    <div style='background:#f8fafc; border-left: 4px solid #d97706; padding: 15px; margin: 20px 0;'>
                        <p style='margin:0 0 5px 0;'><strong>Name:</strong> {$full_name}</p>
                        <p style='margin:0 0 5px 0;'><strong>Role:</strong> {$role}</p>
                        <p style='margin:0;'><strong>Email:</strong> {$email}</p>
                    </div>
                    <p>Please log in to the Admin Dashboard to review and approve this account.</p>
                </div>" . $footer;
            $mail->send();

        } catch (Exception $e) {
            error_log("PHPMailer Error during personnel registration: " . $e->getMessage());
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
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../config/database.php";

$user_id = $_SESSION['user'];

// Find Student ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $student['id'];
$service_type = $_POST['service_type'] ?? '';
$requested_personnel_id = intval($_POST['requested_personnel_id']);
$contract_file_path = null;

// Determine where to redirect based on service type
$redirect_map = [
    'Statistician' => 'students_rs_statistician',
    'Ethics' => 'students_rs_ethics',
    'Librarian' => 'students_rs_librarian',
    'Human Grammarian' => 'students_rs_human_grammarian'
];
$redirect_page = $redirect_map[$service_type] ?? 'dashboard';
$redirect_url = "../../frontend/dashboards/student_dashboard.php?page=" . $redirect_page;

if (!$requested_personnel_id || empty($service_type)) {
    $_SESSION['flash_error'] = "Invalid request.";
    header("Location: " . $redirect_url);
    exit();
}

// Handle Optional Contract File Upload
if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['contract_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
        $filename = time() . "_contract_" . $student_id . "." . $ext;
        $targetDir = "../../uploads/contracts/";
        
        // Ensure directory exists
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            $contract_file_path = $filename;
        }
    }
}

// Insert into service_applications
$insertStmt = $conn->prepare("INSERT INTO service_applications (student_id, service_type, requested_personnel_id, contract_file_path, status) VALUES (?, ?, ?, ?, 'Pending')");
$insertStmt->bind_param("isis", $student_id, $service_type, $requested_personnel_id, $contract_file_path);

if ($insertStmt->execute()) {
    $_SESSION['flash_success'] = "Application submitted! Waiting for Admin approval.";
} else {
    $_SESSION['flash_error'] = "Database error. Please try again.";
}

$insertStmt->close();
header("Location: " . $redirect_url);
exit();
?>
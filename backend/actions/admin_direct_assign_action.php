<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $service_type = $_POST['service_type'];
    $assigned_id = intval($_POST['assigned_personnel_id']);

    if (!$student_id || !$service_type || !$assigned_id) {
        $_SESSION['flash_error'] = "Missing assignment details.";
        header("Location: ../../frontend/dashboards/admin_dashboard.php?page=assign_personnel");
        exit();
    }

    // Check if an application already exists for this student and service
    $checkStmt = $conn->prepare("SELECT id FROM service_applications WHERE student_id = ? AND service_type = ?");
    $checkStmt->bind_param("is", $student_id, $service_type);
    $checkStmt->execute();
    $res = $checkStmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Update existing record
        $app_id = $row['id'];
        $updateStmt = $conn->prepare("UPDATE service_applications SET assigned_personnel_id = ?, status = 'Approved' WHERE id = ?");
        $updateStmt->bind_param("ii", $assigned_id, $app_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['flash_success'] = "$service_type Personnel successfully re-assigned!";
        } else {
            $_SESSION['flash_error'] = "Failed to update assignment.";
        }
        $updateStmt->close();
    } else {
        // Insert new record directly into "Approved" status
        $insertStmt = $conn->prepare("INSERT INTO service_applications (student_id, service_type, assigned_personnel_id, status) VALUES (?, ?, ?, 'Approved')");
        $insertStmt->bind_param("isi", $student_id, $service_type, $assigned_id);
        
        if ($insertStmt->execute()) {
            $_SESSION['flash_success'] = "$service_type Personnel officially assigned!";
        } else {
            $_SESSION['flash_error'] = "Failed to assign personnel.";
        }
        $insertStmt->close();
    }
    
    $checkStmt->close();
    
    header("Location: ../../frontend/dashboards/admin_dashboard.php?page=assign_personnel");
    exit();
}
?>
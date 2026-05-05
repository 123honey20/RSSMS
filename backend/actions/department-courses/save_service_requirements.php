<?php
session_start();
require_once "../../config/database.php";

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['flash_error'] = "Unauthorized access.";
    header("Location: ../../../frontend/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = intval($_POST['department_id']);
    $service_type = trim($_POST['service_type']);
    $required_phases = intval($_POST['required_phases']);
    $round_limit = intval($_POST['round_limit_per_phase']);

    // Extra Security: Prevent Grammarly & AI from being processed
    if ($service_type === 'Grammarly & AI Checking') {
        $_SESSION['flash_error'] = "Grammarly & AI Checking does not support phase configurations.";
        header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=service_requirements");
        exit();
    }

    // Basic Validation
    if (empty($department_id) || empty($service_type) || $required_phases < 1 || $round_limit < 1) {
        $_SESSION['flash_error'] = "Please fill in all fields correctly.";
        header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=service_requirements");
        exit();
    }

    // Insert or Update the rule (Using ON DUPLICATE KEY UPDATE)
    $stmt = $conn->prepare("
        INSERT INTO department_service_requirements 
        (department_id, service_type, required_phases, round_limit_per_phase) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        required_phases = VALUES(required_phases), 
        round_limit_per_phase = VALUES(round_limit_per_phase)
    ");
    
    $stmt->bind_param("isii", $department_id, $service_type, $required_phases, $round_limit);
    
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Service requirements saved successfully!";
    } else {
        $_SESSION['flash_error'] = "Database error: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=service_requirements");
    exit();
}
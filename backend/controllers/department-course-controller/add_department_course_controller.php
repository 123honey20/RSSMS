<?php
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD DEPARTMENT
    if ($action === 'add_department') {
        $department_name = trim($_POST['department_name']);

        if (!empty($department_name)) {
            // NEW: Check if department already exists
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ?");
            $check->bind_param("s", $department_name);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=add_department&error=duplicate_dept");
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $department_name);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_departments&success=added");
                exit();
            } else {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=add_department&error=failed");
                exit();
            }
        }
    }

    // ADD COURSE
    if ($action === 'add_course') {
        $course_name = trim($_POST['course_name']);
        $department_id = intval($_POST['department_id']);

        if (!empty($course_name) && $department_id > 0) {
            // NEW: Check if course already exists IN THIS department
            $check = $conn->prepare("SELECT id FROM courses WHERE name = ? AND department_id = ?");
            $check->bind_param("si", $course_name, $department_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=add_course&error=duplicate_course");
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
            $stmt->bind_param("si", $course_name, $department_id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_courses&success=added");
                exit();
            } else {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=add_course&error=failed");
                exit();
            }
        }
    }
}
?>
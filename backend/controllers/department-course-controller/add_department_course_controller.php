<?php
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ADD DEPARTMENT
    if ($action === 'add_department') {

        $department_name = trim($_POST['department_name']);

        if (!empty($department_name)) {

            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $department_name);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_departments&success=added");
                exit();
            } else {
                echo "Error adding department.";
            }
        }
    }

    // ADD COURSE
    if ($action === 'add_course') {

        $course_name = trim($_POST['course_name']);
        $department_id = intval($_POST['department_id']);

        if (!empty($course_name) && $department_id > 0) {

            $stmt = $conn->prepare(
                "INSERT INTO courses (name, department_id) VALUES (?, ?)"
            );

            $stmt->bind_param("si", $course_name, $department_id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_courses&success=added");
                exit();
            } else {
                echo "Error adding course.";
            }
        }
    }
}

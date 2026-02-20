<?php
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // UPDATE DEPARTMENT
    if ($action === 'update_department') {

        $id = intval($_POST['department_id']);
        $name = trim($_POST['department_name']);

        if ($id > 0 && !empty($name)) {

            $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_departments&success=updated");
                exit();
            } else {
                echo "Error updating department.";
            }
        }
    }

    // UPDATE COURSE
    if ($action === 'update_course') {

        $id = intval($_POST['course_id']);
        $name = trim($_POST['course_name']);
        $department_id = intval($_POST['department_id']);

        if ($id > 0 && !empty($name) && $department_id > 0) {

            $stmt = $conn->prepare(
                "UPDATE courses SET name = ?, department_id = ? WHERE id = ?"
            );

            $stmt->bind_param("sii", $name, $department_id, $id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_courses&success=updated");
                exit();
            } else {
                echo "Error updating course.";
            }
        }
    }
}

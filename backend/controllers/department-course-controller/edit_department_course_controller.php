<?php
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // UPDATE DEPARTMENT
    if ($action === 'update_department') {
        $id = intval($_POST['department_id']);
        $name = trim($_POST['department_name']);

        // Capture Checkbox values (1 if checked, 0 if unchecked)
        $req_grammarly_ai = isset($_POST['req_grammarly_ai']) ? 1 : 0;
        $req_ethics = isset($_POST['req_ethics']) ? 1 : 0;
        $req_human_grammarian = isset($_POST['req_human_grammarian']) ? 1 : 0;
        $req_librarian = isset($_POST['req_librarian']) ? 1 : 0;
        $req_statistician = isset($_POST['req_statistician']) ? 1 : 0;

        if ($id > 0 && !empty($name)) {
            // Check for duplicate name
            $check = $conn->prepare("SELECT id FROM departments WHERE name = ? AND id != ?");
            $check->bind_param("si", $name, $id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=edit_department&id=$id&error=duplicate_dept");
                exit();
            }

            // Update Name AND Service Requirements
            $stmt = $conn->prepare("UPDATE departments SET name = ?, req_grammarly_ai = ?, req_ethics = ?, req_human_grammarian = ?, req_librarian = ?, req_statistician = ? WHERE id = ?");
            $stmt->bind_param("siiiiii", $name, $req_grammarly_ai, $req_ethics, $req_human_grammarian, $req_librarian, $req_statistician, $id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_departments&success=updated");
                exit();
            } else {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=edit_department&id=$id&error=failed");
                exit();
            }
        }
    }

    // UPDATE COURSE
    if ($action === 'update_course') {
        $id = intval($_POST['course_id']);
        $name = trim($_POST['course_name']);
        $department_id = intval($_POST['department_id']);

        if ($id > 0 && !empty($name) && $department_id > 0) {
            // Check if ANOTHER course already has this name inside the same department
            $check = $conn->prepare("SELECT id FROM courses WHERE name = ? AND department_id = ? AND id != ?");
            $check->bind_param("sii", $name, $department_id, $id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=edit_course&id=$id&error=duplicate_course");
                exit();
            }

            $stmt = $conn->prepare("UPDATE courses SET name = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $department_id, $id);

            if ($stmt->execute()) {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=view_courses&success=updated");
                exit();
            } else {
                header("Location: ../../../frontend/dashboards/admin_dashboard.php?page=edit_course&id=$id&error=failed");
                exit();
            }
        }
    }
}
?>
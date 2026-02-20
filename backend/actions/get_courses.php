<?php
require_once "../config/database.php";

if (isset($_GET['department_id'])) {

    $department_id = (int) $_GET['department_id'];

    $stmt = $conn->prepare("SELECT id, name FROM courses WHERE department_id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $courses = [];

    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    echo json_encode($courses);
}

<?php
require_once "../config/database.php";

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$offset = ($page - 1) * $limit;

/* Count total (with search) */
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE name LIKE ?");
    $searchParam = "%" . $search . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $countResult = $stmt->get_result();
} else {
    $countResult = $conn->query("SELECT COUNT(*) as total FROM courses");
}

$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

/* Fetch courses */
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT c.id, c.name AS course_name, d.name AS department_name
        FROM courses c
        JOIN departments d ON c.department_id = d.id
        WHERE c.name LIKE ?
        ORDER BY c.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $searchParam, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "
        SELECT c.id, c.name AS course_name, d.name AS department_name
        FROM courses c
        JOIN departments d ON c.department_id = d.id
        ORDER BY c.id DESC
        LIMIT $limit OFFSET $offset
    ";
    $result = $conn->query($query);
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "courses" => $data,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);

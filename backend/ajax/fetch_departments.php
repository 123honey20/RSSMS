<?php
session_start();
require_once "../config/database.php";

$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$offset = ($page - 1) * $limit;

// Count total departments (with search)
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM departments WHERE name LIKE ?");
    $searchParam = "%" . $search . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $countResult = $stmt->get_result();
} else {
    $countResult = $conn->query("SELECT COUNT(*) as total FROM departments");
}

$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch departments
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM departments WHERE name LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $searchParam, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM departments ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($query);
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "departments" => $data,
    "totalPages" => $totalPages,
    "currentPage" => $page,
    "totalRows" => $totalRows
]);

<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$service = $_GET['service'] ?? '';
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (empty($service) || $course_id === 0) {
    echo json_encode([]);
    exit;
}

// Map service to a safe slug
$service_map = [
    'Grammarly & AI Checking' => 'grammarly_ai',
    'Librarian'               => 'librarian',
    'Human Grammarian'        => 'human_grammarian',
    'Ethics'                  => 'ethics',
    'Statistician'            => 'statistician'
];

$slug = $service_map[$service] ?? '';
if (empty($slug)) {
    echo json_encode([]);
    exit;
}

// Generate the composite key! Example: req_desc_ethics_5
$setting_key = "req_desc_" . $slug . "_" . $course_id;

$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->bind_param("s", $setting_key);
$stmt->execute();
$res = $stmt->get_result();

$requirements = [];
if ($row = $res->fetch_assoc()) {
    $arr = json_decode($row['setting_value'], true);
    if (is_array($arr)) {
        $requirements = $arr;
    } else {
        // Fallback for badly formatted text
        $requirements = array_filter(explode("\n", trim($row['setting_value'])));
    }
}

$stmt->close();
echo json_encode(array_values($requirements));
?>
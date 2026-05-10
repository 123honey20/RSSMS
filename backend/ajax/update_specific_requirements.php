<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['service']) || empty($data['course_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid data received."]);
    exit;
}

$service = trim($data['service']);
$course_id = (int)$data['course_id'];
$requirements = isset($data['requirements']) && is_array($data['requirements']) ? $data['requirements'] : [];

$service_map = [
    'Grammarly & AI Checking' => 'grammarly_ai',
    'Librarian'               => 'librarian',
    'Human Grammarian'        => 'human_grammarian',
    'Ethics'                  => 'ethics',
    'Statistician'            => 'statistician'
];

$slug = $service_map[$service] ?? '';
if (empty($slug)) {
    echo json_encode(["success" => false, "message" => "Invalid service selected."]);
    exit;
}

// Generate the composite key! Example: req_desc_ethics_5
$setting_key = "req_desc_" . $slug . "_" . $course_id;
$setting_value = json_encode($requirements);

// Insert or update
$stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
$stmt->bind_param("ss", $setting_key, $setting_value);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Requirements successfully saved for this course!"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error. Failed to save requirements."]);
}

$stmt->close();
?>
<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid data received."]);
    exit;
}

// Define the allowed keys to prevent SQL injection or bad data
$allowed_keys = [
    'req_desc_grammarly_ai', 
    'req_desc_ethics', 
    'req_desc_human_grammarian', 
    'req_desc_librarian', 
    'req_desc_statistician'
];

$success = true;

foreach ($allowed_keys as $key) {
    if (isset($data[$key])) {
        // Using INSERT ... ON DUPLICATE KEY UPDATE so it works even if the row doesn't exist yet
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $value = trim($data[$key]);
        $stmt->bind_param("ss", $key, $value);
        if (!$stmt->execute()) {
            $success = false;
        }
        $stmt->close();
    }
}

if ($success) {
    echo json_encode(["success" => true, "message" => "Requirements successfully saved!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save some requirements."]);
}
?>
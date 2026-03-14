<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

// Ensure only admins can do this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || empty($data['name']) || empty($data['criteria'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

try {
    // Start Transaction
    $conn->begin_transaction();

    // 1. Insert the Rubric Name
    $stmt1 = $conn->prepare("INSERT INTO rubrics (name) VALUES (?)");
    $stmt1->bind_param("s", $data['name']);
    $stmt1->execute();
    
    // Get the ID of the rubric we just created
    $rubric_id = $conn->insert_id;
    $stmt1->close();

    // 2. Loop through each Criterion
    $stmt2 = $conn->prepare("INSERT INTO rubric_criteria (rubric_id, name, description, weight) VALUES (?, ?, ?, ?)");
    $stmt3 = $conn->prepare("INSERT INTO rubric_levels (criterion_id, score, description) VALUES (?, ?, ?)");

    foreach ($data['criteria'] as $criterion) {
        $c_name = $criterion['name'];
        $c_desc = $criterion['description'];
        $c_weight = intval($criterion['weight']);

        $stmt2->bind_param("issi", $rubric_id, $c_name, $c_desc, $c_weight);
        $stmt2->execute();

        // Get the ID of the criterion we just created
        $criterion_id = $conn->insert_id;

        // 3. Loop through each Scoring Level for this Criterion
        if (isset($criterion['levels']) && is_array($criterion['levels'])) {
            foreach ($criterion['levels'] as $level) {
                $l_score = intval($level['score']);
                $l_desc = $level['desc'];

                $stmt3->bind_param("iis", $criterion_id, $l_score, $l_desc);
                $stmt3->execute();
            }
        }
    }

    $stmt2->close();
    $stmt3->close();

    // If we made it here without errors, COMMIT the transaction to the database
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Rubric saved successfully!']);

} catch (Exception $e) {
    // If anything fails, ROLLBACK the changes so we don't end up with partial data
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || empty($data['id']) || empty($data['name']) || empty($data['criteria'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$rubric_id = intval($data['id']);

try {
    $conn->begin_transaction();

    $stmt1 = $conn->prepare("UPDATE rubrics SET name = ? WHERE id = ?");
    $stmt1->bind_param("si", $data['name'], $rubric_id);
    $stmt1->execute();
    $stmt1->close();

    $stmtDelete = $conn->prepare("DELETE FROM rubric_criteria WHERE rubric_id = ?");
    $stmtDelete->bind_param("i", $rubric_id);
    $stmtDelete->execute();
    $stmtDelete->close();

    $stmt2 = $conn->prepare("INSERT INTO rubric_criteria (rubric_id, name, description, weight) VALUES (?, ?, ?, ?)");
    $stmt3 = $conn->prepare("INSERT INTO rubric_levels (criterion_id, score, description) VALUES (?, ?, ?)");

    foreach ($data['criteria'] as $criterion) {
        $c_name = $criterion['name'];
        $c_desc = $criterion['description'];
        $c_weight = intval($criterion['weight']);

        $stmt2->bind_param("issi", $rubric_id, $c_name, $c_desc, $c_weight);
        $stmt2->execute();

        $criterion_id = $conn->insert_id;

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

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Rubric updated successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
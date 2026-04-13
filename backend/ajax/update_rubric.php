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

    // 1. Update the Rubric Name
    $stmt1 = $conn->prepare("UPDATE rubrics SET name = ? WHERE id = ?");
    $stmt1->bind_param("si", $data['name'], $rubric_id);
    $stmt1->execute();
    $stmt1->close();

    // Array to track which criteria IDs we are keeping
    $keep_criteria_ids = [];

    $stmtUpdateCrit = $conn->prepare("UPDATE rubric_criteria SET name = ?, description = ?, weight = ? WHERE id = ? AND rubric_id = ?");
    $stmtInsertCrit = $conn->prepare("INSERT INTO rubric_criteria (rubric_id, name, description, weight) VALUES (?, ?, ?, ?)");
    $stmtInsertLevel = $conn->prepare("INSERT INTO rubric_levels (criterion_id, score, description) VALUES (?, ?, ?)");

    // 2. Process Criteria intelligently (UPDATE existing, INSERT new)
    foreach ($data['criteria'] as $criterion) {
        $c_name = $criterion['name'];
        $c_desc = $criterion['description'];
        $c_weight = intval($criterion['weight']);

        if (!empty($criterion['id'])) {
            // UPDATE EXISTING: Preserves historical student feedback
            $c_id = intval($criterion['id']);
            $stmtUpdateCrit->bind_param("ssiii", $c_name, $c_desc, $c_weight, $c_id, $rubric_id);
            $stmtUpdateCrit->execute();
            $keep_criteria_ids[] = $c_id;
        } else {
            // INSERT NEW
            $stmtInsertCrit->bind_param("issi", $rubric_id, $c_name, $c_desc, $c_weight);
            $stmtInsertCrit->execute();
            $c_id = $conn->insert_id;
            $keep_criteria_ids[] = $c_id;
        }

        // 3. Process Levels (Safe to delete and recreate because student ratings only store the raw integer score)
        $conn->query("DELETE FROM rubric_levels WHERE criterion_id = $c_id");

        if (isset($criterion['levels']) && is_array($criterion['levels'])) {
            foreach ($criterion['levels'] as $level) {
                $l_score = intval($level['score']);
                $l_desc = $level['desc'];

                $stmtInsertLevel->bind_param("iis", $c_id, $l_score, $l_desc);
                $stmtInsertLevel->execute();
            }
        }
    }

    $stmtUpdateCrit->close();
    $stmtInsertCrit->close();
    $stmtInsertLevel->close();

    // 4. Safely remove any criteria the Admin deleted in the UI
    if (!empty($keep_criteria_ids)) {
        $ids_to_keep = implode(',', $keep_criteria_ids);
        
        // Note: If you have strict Foreign Keys, deleting a criterion that has past feedback will throw an error 
        // and trigger the catch block. This is actually a good safety net to prevent destroying history!
        $conn->query("DELETE FROM rubric_criteria WHERE rubric_id = $rubric_id AND id NOT IN ($ids_to_keep)");
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Rubric updated successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    // Check if it's a Foreign Key constraint failure
    if ($conn->errno === 1451) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete a criterion that has already been used in student evaluations.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
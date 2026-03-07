<?php
session_start();
require_once "../../config/database.php";

$user_id = $_SESSION['user'];

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Get latest round
$latestRes = $conn->query("
    SELECT round FROM grammarly_ai_transactions
    WHERE student_id = $student_id
    ORDER BY round DESC
    LIMIT 1
");
$latest = $latestRes->fetch_assoc();
$newRound = $latest ? ((int)$latest['round'] + 1) : 1;

if ($newRound > 7) {
    die("Maximum 7 rounds reached.");
}

$stmt = $conn->prepare("
    INSERT INTO grammarly_ai_transactions (student_id, round)
    VALUES (?, ?)
");

$stmt->bind_param("ii", $student_id, $newRound);
$stmt->execute();

header("Location: ../../../frontend/dashboards/student_dashboard.php?page=student_transaction_grammarly_ai");
exit();

<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";

$user_id = $_SESSION['user'];

// Get student id
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Get school_id of student
$stmt2 = $conn->prepare("SELECT school_id FROM users WHERE id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$userRow = $res2->fetch_assoc();
$school_id = $userRow['school_id'];


// File handling
$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/grammarly_ai/submissions/";
$targetFile = $targetDir . $filename;

// Extension check
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed = ['pdf', 'docx', 'doc', 'odt', 'rtf', 'txt', 'pptx'];

if (!in_array($ext, $allowed)) {
    die("Only PDF, DOCX, DOC, ODT, RTF, TXT, and PPTX files are allowed.");
}

// MIME type check
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

$allowedMime = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
    'application/msword', // doc
    'application/vnd.oasis.opendocument.text', // odt
    'application/rtf', // rtf
    'text/plain', // txt
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' // pptx
];

if (!in_array($mime, $allowedMime)) {
    die("Invalid file type.");
}


if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    // Get latest approved transaction
    $stmt = $conn->prepare("
        SELECT * FROM grammarly_ai_transactions
        WHERE student_id = ?
        AND status = 'Approved'
        ORDER BY round DESC
        LIMIT 1
    ");

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $approvedTransaction = $result->fetch_assoc();

    if (!$approvedTransaction) {
        die("No approved transaction found. Please complete payment first.");
    }

    $round = (int)$approvedTransaction['round'];

    // Check if submission already exists for this round
    $checkSubmission = $conn->query("
    SELECT * FROM grammarly_ai
    WHERE student_id = $student_id
    AND round = $round
    LIMIT 1
");

    $existingSubmission = $checkSubmission->fetch_assoc();

    if ($existingSubmission) {

        if ($existingSubmission['status'] === 'Pending') {
            // Re-upload same round
            $stmt = $conn->prepare("
            UPDATE grammarly_ai
            SET file_path = ?, status = 'Pending'
            WHERE id = ?
        ");
            $stmt->bind_param("si", $filename, $existingSubmission['id']);
            $stmt->execute();

            $_SESSION['flash_success'] = "Submission re-uploaded for Round $round.";
        } elseif ($existingSubmission['status'] === 'Approved') {
            die("This round is already approved.");
        } else {
            // Rejected submission -> DO NOT ALLOW UPDATE. They must request a new transaction round.
            die("This submission was rejected. You must request a new transaction round from the dashboard.");
        }
    } else {
        // Insert new submission for approved round
        $stmt = $conn->prepare("
        INSERT INTO grammarly_ai (student_id, school_id, file_path, status, round)
        VALUES (?, ?, ?, 'Pending', ?)
    ");
        $stmt->bind_param("issi", $student_id, $school_id, $filename, $round);
        $stmt->execute();

        $_SESSION['flash_success'] = "Submission uploaded successfully for Round $round.";
    }

    header("Location: ../../../frontend/dashboards/student_dashboard.php?page=student_upload_grammarly_ai");
    exit();
} else {
    echo "File upload failed.";
}

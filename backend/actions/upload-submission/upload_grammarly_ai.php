<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";

$user_id = $_SESSION['user'];
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_grammarly_ai";

function redirectWithError($message, $url) {
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

// Get student id
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];
$stmt->close();

// Get school_id of student
$stmt2 = $conn->prepare("SELECT school_id FROM users WHERE id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$userRow = $stmt2->get_result()->fetch_assoc();
$school_id = $userRow['school_id'];
$stmt2->close();

// File handling
if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file to upload.", $redirect_url);
}

$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/grammarly_ai/submissions/";
$targetFile = $targetDir . $filename;

// Extension check
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed = ['pdf', 'docx', 'doc', 'odt', 'rtf', 'txt', 'pptx'];

if (!in_array($ext, $allowed)) {
    redirectWithError("Only PDF, DOCX, DOC, ODT, RTF, TXT, and PPTX files are allowed.", $redirect_url);
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
    redirectWithError("Invalid file format detected.", $redirect_url);
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
    $approvedTransaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$approvedTransaction) {
        // Delete the file we just uploaded because they aren't allowed to submit yet!
        unlink($targetFile);
        redirectWithError("No approved transaction found. Please complete payment first.", $redirect_url);
    }

    $round = (int)$approvedTransaction['round'];

    // Check if submission already exists for this round
    $checkSubmission = $conn->prepare("SELECT * FROM grammarly_ai WHERE student_id = ? AND round = ? LIMIT 1");
    $checkSubmission->bind_param("ii", $student_id, $round);
    $checkSubmission->execute();
    $existingSubmission = $checkSubmission->get_result()->fetch_assoc();
    $checkSubmission->close();

    if ($existingSubmission) {

        if ($existingSubmission['status'] === 'Pending') {
            
            // FIX: Delete the old file from the server to save space!
            $oldFilePath = $targetDir . $existingSubmission['file_path'];
            if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                unlink($oldFilePath);
            }

            // Re-upload same round
            $stmt = $conn->prepare("UPDATE grammarly_ai SET file_path = ?, status = 'Pending' WHERE id = ?");
            $stmt->bind_param("si", $filename, $existingSubmission['id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = "Submission re-uploaded for Round $round.";

        } elseif ($existingSubmission['status'] === 'Approved') {
            unlink($targetFile); // Delete the file they just tried to upload
            redirectWithError("This round is already approved. You cannot overwrite it.", $redirect_url);

        } else {
            unlink($targetFile); // Delete the file they just tried to upload
            redirectWithError("This submission was rejected. You must request a new transaction round.", $redirect_url);
        }

    } else {
        // Insert new submission for approved round
        $stmt = $conn->prepare("
            INSERT INTO grammarly_ai (student_id, school_id, file_path, status, round)
            VALUES (?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("issi", $student_id, $school_id, $filename, $round);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = "Submission uploaded successfully for Round $round.";
    }

    header("Location: " . $redirect_url);
    exit();
} else {
    redirectWithError("File upload failed. Please try again.", $redirect_url);
}
?>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";

$user_id = $_SESSION['user'];
$redirect_url = "../../../frontend/dashboards/student_dashboard.php?page=student_upload_human_grammarian";

function redirectWithError($message, $url, $fileToTrash = null) {
    if ($fileToTrash && file_exists($fileToTrash) && is_file($fileToTrash)) unlink($fileToTrash);
    $_SESSION['flash_error'] = $message;
    header("Location: " . $url);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_id = $stmt->get_result()->fetch_assoc()['id'];
$stmt->close();

$stmt2 = $conn->prepare("SELECT school_id FROM users WHERE id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$school_id = $stmt2->get_result()->fetch_assoc()['school_id'];
$stmt2->close();

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError("Please select a valid file to upload.", $redirect_url);
}

$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/human_grammarian/";
$targetFile = $targetDir . $filename;

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed = ['pdf', 'docx', 'doc', 'odt', 'rtf', 'txt', 'pptx'];

if (!in_array($ext, $allowed)) {
    redirectWithError("Only PDF, DOCX, DOC, ODT, RTF, TXT, and PPTX files are allowed.", $redirect_url);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowedMime = [
    'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword', 'application/vnd.oasis.opendocument.text', 'application/rtf',
    'text/plain', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
];

if (!in_array($mime, $allowedMime)) {
    redirectWithError("Invalid file format detected.", $redirect_url);
}

if (move_uploaded_file($file['tmp_name'], $targetFile)) {

    $checkStmt = $conn->prepare("SELECT * FROM human_grammarian WHERE student_id = ? ORDER BY round DESC LIMIT 1");
    $checkStmt->bind_param("i", $student_id);
    $checkStmt->execute();
    $latest = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($latest) {
        $currentRound = (int)$latest['round'];
        $status = $latest['status'];

        if ($status === 'Pending') {
            $oldFilePath = $targetDir . $latest['file_path'];
            if (file_exists($oldFilePath) && is_file($oldFilePath)) unlink($oldFilePath);

            $stmt = $conn->prepare("UPDATE human_grammarian SET file_path = ?, status = 'Pending' WHERE id = ?");
            $stmt->bind_param("si", $filename, $latest['id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = "Submission re-uploaded (same round).";
        } elseif ($status === 'Rejected') {
            if ($currentRound >= 7) redirectWithError("You have reached the maximum of 7 rounds.", $redirect_url, $targetFile);

            $newRound = $currentRound + 1;
            $stmt = $conn->prepare("INSERT INTO human_grammarian (student_id, school_id, file_path, status, round) VALUES (?, ?, ?, 'Pending', ?)");
            $stmt->bind_param("issi", $student_id, $school_id, $filename, $newRound);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = "New round ($newRound) submitted successfully.";
        } else {
            redirectWithError("Submission already approved. Upload disabled.", $redirect_url, $targetFile);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO human_grammarian (student_id, school_id, file_path, status, round) VALUES (?, ?, ?, 'Pending', 1)");
        $stmt->bind_param("iss", $student_id, $school_id, $filename);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = "Submission uploaded successfully (Round 1).";
    }

    header("Location: " . $redirect_url);
    exit();
} else {
    redirectWithError("File upload failed. Please try again.", $redirect_url);
}
?>
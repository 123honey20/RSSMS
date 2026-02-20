<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../frontend/auth/login.php");
    exit();
}

require_once "../../config/database.php";

$user_id = $_SESSION['user'];

// Get student id
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Get school_id of student
$res2 = $conn->query("SELECT school_id FROM users WHERE id = $user_id");
$userRow = $res2->fetch_assoc();
$school_id = $userRow['school_id'];


// File handling
$file = $_FILES['submission_file'];
$filename = time() . "_" . basename($file['name']);
$targetDir = "../../../uploads/";
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

    // Check if already has submission
    $check = $conn->query("
    SELECT * FROM human_grammarian 
    WHERE student_id = $student_id 
    ORDER BY round DESC 
    LIMIT 1
    ");

    $latest = $check->fetch_assoc();


    if ($latest) {
        $currentRound = (int)$latest['round'];
        $status = $latest['status'];

        if ($status === 'Pending') {
            // Re-upload same round
            $stmt = $conn->prepare("
            UPDATE human_grammarian 
            SET file_path = ?, status = 'Pending' 
            WHERE id = ?
        ");
            $stmt->bind_param("si", $filename, $latest['id']);
            $stmt->execute();

            $_SESSION['flash_success'] = "Submission re-uploaded (same round).";
        } elseif ($status === 'Rejected') {

            if ($currentRound >= 7) {
                die("You have reached the maximum of 7 rounds.");
            }

            $newRound = $currentRound + 1;

            $stmt = $conn->prepare("
            INSERT INTO human_grammarian (student_id, school_id, file_path, status, round) 
            VALUES (?, ?, ?, 'Pending', ?)
        ");
            $stmt->bind_param("issi", $student_id, $school_id, $filename, $newRound);
            $stmt->execute();

            $_SESSION['flash_success'] = "New round ($newRound) submitted successfully.";
        } else {
            // Approved
            die("Submission already approved. Upload disabled.");
        }
    } else {
        // First submission = round 1
        $stmt = $conn->prepare("
        INSERT INTO human_grammarian (student_id, school_id, file_path, status, round) 
        VALUES (?, ?, ?, 'Pending', 1)
    ");
        $stmt->bind_param("iss", $student_id, $school_id, $filename);
        $stmt->execute();

        $_SESSION['flash_success'] = "Submission uploaded successfully (Round 1).";
    }


    header("Location: ../../../frontend/dashboards/student_dashboard.php?page=student_upload_human_grammarian");
    exit();
} else {
    echo "File upload failed.";
}

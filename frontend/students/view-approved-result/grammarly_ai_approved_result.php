<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

// Get submission to verify
$stmt = $conn->prepare("
    SELECT g.*, s.control_number 
    FROM grammarly_ai g
    JOIN students s ON g.student_id = s.id
    WHERE g.id = ? AND s.user_id = ? AND g.status = 'Approved'
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg max-w-4xl mx-auto mt-8'>Approved result not found or unauthorized access.</div>";
    exit();
}
?>

<div class="max-w-4xl mx-auto py-6 px-4 w-full">

    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Approved Result</h1>
            <p class="text-sm text-gray-500 mt-1">Grammarly & AI Checking Service Verification</p>
        </div>
        <a href="student_dashboard.php?page=students_rs_grammarly_ai"
            class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Grammarly & AI Checking
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-green-100 overflow-hidden">

        <div class="bg-gradient-to-r from-blue-600 to-blue-300 px-6 py-8 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>

            <div class="relative z-10 flex flex-col items-center">
                <div class="bg-white p-3 rounded-full shadow-md mb-3 text-blue-500 inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-1">Submission Approved!</h2>
                <p class="text-green-50 text-sm max-w-md mx-auto">
                    Your research document (Control No: <?php echo htmlspecialchars($submission['control_number']); ?>) has successfully approved.
                </p>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-gray-100">
            <h3 class="text-gray-700 font-semibold text-base mb-5 text-center">What would you like to do next?</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">

                <a href="student_dashboard.php?page=student_certificate_grammarly_ai&id=<?php echo $submissionId; ?>" class="group flex items-center p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm transition-all cursor-pointer text-left">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0 mr-4 group-hover:scale-105 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-blue-700 font-bold text-sm">View Certificate</span>
                        <span class="block text-gray-500 text-xs mt-0.5 leading-snug">Download or print your approval certificate.</span>
                    </div>
                </a>

                <a href="student_dashboard.php?page=student_feedback_grammarly_ai&id=<?php echo $submissionId; ?>" class="group flex items-center p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm transition-all cursor-pointer text-left">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0 mr-4 group-hover:scale-105 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-blue-700 font-bold text-sm">Rate & Feedback</span>
                        <span class="block text-gray-500 text-xs mt-0.5 leading-snug">Share your experience to help us improve.</span>
                    </div>
                </a>

            </div>
        </div>
    </div>
</div>
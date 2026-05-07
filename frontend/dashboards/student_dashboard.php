<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

// 1. Get ALL Student Profile Details AND Department Requirements
$stmt = $conn->prepare("
    SELECT u.school_id, u.email, 
           s.id AS student_id, s.control_number, s.thesis_title, s.research_leader,
           d.name AS department_name, c.name AS course_name,
           d.req_grammarly_ai, d.req_ethics, d.req_human_grammarian, d.req_librarian, d.req_statistician
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_id = $profile['student_id'] ?? 0;
$_SESSION['control_number'] = $profile['control_number'] ?? '';

// 2. Check if the student is Approved in ONLY their REQUIRED research services
$servicesToCheck = [];
if ($profile['req_grammarly_ai'] ?? 1) $servicesToCheck[] = 'grammarly_ai';
if ($profile['req_ethics'] ?? 1) $servicesToCheck[] = 'ethics';
if ($profile['req_human_grammarian'] ?? 1) $servicesToCheck[] = 'human_grammarian';
if ($profile['req_librarian'] ?? 1) $servicesToCheck[] = 'librarian';
if ($profile['req_statistician'] ?? 1) $servicesToCheck[] = 'statistician';

$isEligibleForProposal = true;

if ($student_id > 0 && !empty($servicesToCheck)) {
    foreach ($servicesToCheck as $service) {
        $checkStmt = $conn->prepare("SELECT id FROM $service WHERE student_id = ? AND status = 'Approved' LIMIT 1");
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $isEligibleForProposal = false;
            $checkStmt->close();
            break;
        }
        $checkStmt->close();
    }
} else {
    $isEligibleForProposal = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        warmdark: {
                            bg: '#1c1b16',
                            panel: '#26241c',
                            border: '#3d392b',
                            hover: '#312e23'
                        }
                    }
                }
            }
        }
    </script>

    <script>
        const userId = <?php echo json_encode($user_id); ?>;
        const themeKey = 'theme_user_' + userId;

        if (localStorage.getItem(themeKey) === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#F9FAFB] dark:bg-warmdark-bg text-gray-800 dark:text-gray-200 font-sans antialiased selection:bg-blue-100 selection:text-blue-900 transition-colors duration-200">

    <header class="fixed top-0 left-0 w-full h-[72px] bg-blue-900/95 dark:bg-warmdark-panel/95 backdrop-blur-md text-white shadow-md border-b-[3px] border-b-[#FFC107] dark:border-b-yellow-500 z-50 flex items-center justify-between px-6 transition-all duration-200">
        <div class="flex items-center gap-3">
            <img src="../images/smcc logo.png" alt="Logo" class="w-10 h-10 object-contain drop-shadow-sm">
            <div class="hidden sm:block flex-col justify-center">
                <h1 class="text-sm font-bold tracking-wide leading-tight">RESEARCH SUPPORT SERVICES</h1>
                <p class="text-[10px] text-blue-200 dark:text-yellow-400/80 uppercase tracking-widest font-semibold">Monitoring System</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-[11px] text-blue-200 dark:text-gray-400 font-medium uppercase tracking-wider mb-0.5">Welcome, <?php echo htmlspecialchars($profile['research_leader'] ?? 'Unknown'); ?>
                </p>

            </div>

            <button onclick="openMyProfile()" class="w-10 h-10 rounded-full bg-blue-800 dark:bg-warmdark-hover border border-blue-400/50 dark:border-warmdark-border flex items-center justify-center shadow-inner hover:bg-blue-700 dark:hover:bg-warmdark-border hover:ring-2 hover:ring-blue-300 dark:hover:ring-yellow-500/50 transition-all focus:outline-none group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200 dark:text-gray-300 group-hover:text-white transition-colors" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </header>

    <div class="flex pt-[72px] min-h-screen">

        <aside class="fixed left-0 top-[72px] w-64 h-[calc(100vh-72px)] bg-white dark:bg-warmdark-panel border-r border-gray-200 dark:border-warmdark-border flex flex-col z-40 overflow-y-auto custom-scrollbar transition-colors duration-200">

            <nav class="flex-1 py-5 flex flex-col gap-4">

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Research Services</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>

                        <?php if ($profile['req_grammarly_ai'] ?? 1): ?>
                            <a href="student_dashboard.php?page=students_rs_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Grammarly & AI
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_ethics'] ?? 1): ?>
                            <a href="student_dashboard.php?page=students_rs_ethics" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Ethics
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_human_grammarian'] ?? 1): ?>
                            <a href="student_dashboard.php?page=students_rs_human_grammarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477-4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                Human Grammarian
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_librarian'] ?? 1): ?>
                            <a href="student_dashboard.php?page=students_rs_librarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                Librarian
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_statistician'] ?? 1): ?>
                            <a href="student_dashboard.php?page=students_rs_statistician" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                Statistician
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Communicate</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>

                        <?php if ($profile['req_grammarly_ai'] ?? 1): ?>
                            <a href="student_dashboard.php?page=chat_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-2 h-2 rounded-full bg-emerald-400 shadow-sm ml-1"></div>
                                Grammarly & AI
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_ethics'] ?? 1): ?>
                            <a href="student_dashboard.php?page=chat_ethics" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-2 h-2 rounded-full bg-blue-400 shadow-sm ml-1"></div>
                                Ethics
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_human_grammarian'] ?? 1): ?>
                            <a href="student_dashboard.php?page=chat_human_grammarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-2 h-2 rounded-full bg-purple-400 shadow-sm ml-1"></div>
                                Human Grammarian
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_librarian'] ?? 1): ?>
                            <a href="student_dashboard.php?page=chat_librarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-2 h-2 rounded-full bg-pink-400 shadow-sm ml-1"></div>
                                Librarian
                            </a>
                        <?php endif; ?>

                        <?php if ($profile['req_statistician'] ?? 1): ?>
                            <a href="student_dashboard.php?page=chat_statistician" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-2 h-2 rounded-full bg-red-400 shadow-sm ml-1"></div>
                                Statistician
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Transaction</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>

                        <?php if ($profile['req_grammarly_ai'] ?? 1): ?>
                            <a href="student_dashboard.php?page=student_transaction_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors group">
                                <div class="w-4 h-4 flex items-center justify-center rounded border border-gray-300 dark:border-gray-500 text-gray-400 dark:text-gray-500 bg-white dark:bg-warmdark-bg group-hover:border-gray-400 dark:group-hover:border-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                Grammarly & AI Checking
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

            </nav>

            <div class="p-4 border-t border-gray-100 dark:border-warmdark-border mt-auto flex flex-col gap-1 transition-colors">

                <?php if ($isEligibleForProposal): ?>
                    <a href="student_dashboard.php?page=student_proposal_certificate" class="flex items-center gap-2.5 px-3 py-2 mb-2 rounded-lg bg-gray-900 dark:bg-yellow-600 text-white font-medium hover:bg-gray-800 dark:hover:bg-yellow-500 transition-colors shadow-sm text-[13px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-400 dark:text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Proposal Certificate
                    </a>
                <?php endif; ?>

                <a href="student_dashboard.php?page=student_settings" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>

                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors mt-1 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2-2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <main class="flex-1 ml-64 p-6 lg:p-8 min-h-[calc(100vh-72px)]">
            <div id="main-content" class="max-w-6xl mx-auto">

                <?php
                $page = $_GET['page'] ?? 'dashboard';

                switch ($page) {
                    case 'student_proposal_certificate':
                        include '../certificate/proposal-certificate-student/student_certificate_proposal.php';
                        break;
                    case 'students_rs_grammarly_ai':
                        include '../students/student-research-services/grammarly_ai_checking.php';
                        break;
                    case 'student_view_grammarly_ai_report':
                        include '../students/view-reports/view_grammarly_ai_report.php';
                        break;
                    case 'student_grammarly_ai_approved_result':
                        include '../students/view-approved-result/grammarly_ai_approved_result.php';
                        break;
                    case 'student_feedback_grammarly_ai':
                        include '../feedback/feedback-student/student_feedback_grammarly_ai.php';
                        break;
                    case 'student_certificate_grammarly_ai':
                        include '../certificate/certificate-student/student_certificate_grammarly_ai.php';
                        break;
                    case 'students_rs_ethics':
                        include '../students/student-research-services/ethics.php';
                        break;
                    case 'student_view_ethics_report':
                        include '../students/view-reports/view_ethics_report.php';
                        break;
                    case 'student_ethics_approved_result':
                        include '../students/view-approved-result/ethics_approved_result.php';
                        break;
                    case 'student_feedback_ethics':
                        include '../feedback/feedback-student/student_feedback_ethics.php';
                        break;
                    case 'student_certificate_ethics':
                        include '../certificate/certificate-student/student_certificate_ethics.php';
                        break;
                    case 'students_rs_human_grammarian':
                        include '../students/student-research-services/human_grammarian.php';
                        break;
                    case 'student_view_human_grammarian_report':
                        include '../students/view-reports/view_human_grammarian_report.php';
                        break;
                    case 'student_human_grammarian_approved_result':
                        include '../students/view-approved-result/human_grammarian_approved_result.php';
                        break;
                    case 'student_feedback_human_grammarian':
                        include '../feedback/feedback-student/student_feedback_human_grammarian.php';
                        break;
                    case 'student_certificate_human_grammarian':
                        include '../certificate/certificate-student/student_certificate_human_grammarian.php';
                        break;
                    case 'students_rs_librarian':
                        include '../students/student-research-services/librarian.php';
                        break;
                    case 'student_view_librarian_report':
                        include '../students/view-reports/view_librarian_report.php';
                        break;
                    case 'student_librarian_approved_result':
                        include '../students/view-approved-result/librarian_approved_result.php';
                        break;
                    case 'student_feedback_librarian':
                        include '../feedback/feedback-student/student_feedback_librarian.php';
                        break;
                    case 'student_certificate_librarian':
                        include '../certificate/certificate-student/student_certificate_librarian.php';
                        break;
                    case 'students_rs_statistician':
                        include '../students/student-research-services/statistician.php';
                        break;
                    case 'student_view_statistician_report':
                        include '../students/view-reports/view_statistician_report.php';
                        break;
                    case 'student_statistician_approved_result':
                        include '../students/view-approved-result/statistician_approved_result.php';
                        break;
                    case 'student_feedback_statistician':
                        include '../feedback/feedback-student/student_feedback_statistician.php';
                        break;
                    case 'student_certificate_statistician':
                        include '../certificate/certificate-student/student_certificate_statistician.php';
                        break;
                    case 'student_upload_grammarly_ai':
                        include '../dashboards/student-submission/grammarly_ai_upload.php';
                        break;
                    case 'student_upload_ethics':
                        include '../dashboards/student-submission/ethics_upload.php';
                        break;
                    case 'student_upload_statistician':
                        include '../dashboards/student-submission/statistician_upload.php';
                        break;
                    case 'student_upload_librarian':
                        include '../dashboards/student-submission/librarian_upload.php';
                        break;
                    case 'student_upload_human_grammarian':
                        include '../dashboards/student-submission/human_grammarian_upload.php';
                        break;
                    case 'student_transaction_grammarly_ai':
                        include '../students/transaction/transaction_grammarly_ai.php';
                        break;
                    case 'student_transaction_receipt_grammarly_ai':
                        include '../students/transaction/transaction-receipt-upload/grammarly_ai_receipt.php';
                        break;
                    case 'chat_grammarly_ai':
                        include '../communicate/student-communicate/grammarly-ai-communication/grammarly_ai_chat.php';
                        break;
                    case 'chat_ethics':
                        include '../communicate/student-communicate/ethics-communication/ethics_chat.php';
                        break;
                    case 'chat_human_grammarian':
                        include '../communicate/student-communicate/human-grammarian-communication/human_grammarian_chat.php';
                        break;
                    case 'chat_librarian':
                        include '../communicate/student-communicate/librarian-communication/librarian_chat.php';
                        break;
                    case 'chat_statistician':
                        include '../communicate/student-communicate/statistician-communication/statistician_chat.php';
                        break;
                    case 'student_settings':
                        include '../settings/student-settings/student_settings.php';
                        break;
                    default:
                ?>
                        <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border p-10 flex flex-col items-center justify-center text-center h-[50vh] transition-colors">
                            <div class="w-16 h-16 bg-gray-50 dark:bg-warmdark-bg text-gray-400 dark:text-gray-500 rounded-xl flex items-center justify-center mb-5 border border-gray-100 dark:border-warmdark-border transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 tracking-tight">Dashboard Overview</h2>
                            <p class="text-[13px] text-gray-500 dark:text-gray-400 max-w-md leading-relaxed">Navigate using the sidebar to manage your research submissions, view evaluation feedback, and download approval certificates.</p>
                        </div>
                <?php
                        break;
                }
                ?>

            </div>
        </main>
    </div>

    <div id="myProfileModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity">
        <div class="bg-white dark:bg-warmdark-panel w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform border border-transparent dark:border-warmdark-border">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 dark:from-warmdark-bg dark:to-warmdark-bg text-white px-6 py-4 flex items-center justify-between dark:border-b dark:border-warmdark-border">
                <h3 class="text-lg font-semibold tracking-wide flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    My Profile
                </h3>
                <button onclick="closeMyProfile()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[80vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800 dark:text-gray-200">

                    <div class="md:col-span-2 bg-blue-50/50 dark:bg-warmdark-bg p-4 rounded-xl border border-blue-100 dark:border-warmdark-border flex items-center gap-4 transition-colors">
                        <div class="w-14 h-14 bg-blue-200 dark:bg-warmdark-hover text-blue-800 dark:text-blue-400 rounded-full flex items-center justify-center shadow-inner transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-wider mb-0.5">ID Number</p>
                            <p class="font-bold text-xl text-gray-900 dark:text-gray-100 leading-none"><?php echo htmlspecialchars($profile['school_id'] ?? 'N/A'); ?></p>
                            <p class="text-blue-600 dark:text-blue-400 text-xs font-medium mt-1"><?php echo htmlspecialchars($profile['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="md:col-span-2 border-t border-gray-100 dark:border-warmdark-border my-1 transition-colors"></div>

                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Control Number</p>
                        <span class="font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-0.5 rounded border border-blue-100 dark:border-blue-900/50 inline-block break-words transition-colors">
                            <?php echo htmlspecialchars($profile['control_number'] ?? 'Not Assigned'); ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Research Leader</p>
                        <p class="font-medium text-gray-800 dark:text-gray-300 break-words"><?php echo htmlspecialchars($profile['research_leader'] ?? 'N/A'); ?></p>
                    </div>

                    <div class="md:col-span-2 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border mt-2 transition-colors">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Title</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100 leading-snug"><?php echo htmlspecialchars($profile['thesis_title'] ?? 'N/A'); ?></p>
                    </div>

                    <div class="md:col-span-2 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100 break-words leading-snug"><?php echo htmlspecialchars($profile['department_name'] ?? 'N/A'); ?></p>
                    </div>

                    <div class="md:col-span-2 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Course</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100 break-words leading-snug"><?php echo htmlspecialchars($profile['course_name'] ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button onclick="closeMyProfile()" class="bg-white dark:bg-warmdark-hover border border-gray-300 dark:border-warmdark-border px-6 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-warmdark-border text-gray-700 dark:text-gray-200 text-sm font-bold transition shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadContent(url) {
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                })
                .catch(err => {
                    console.error('Error loading content:', err);
                });
        }

        document.querySelectorAll('[data-load]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadContent(this.getAttribute('data-load'));
            });
        });

        function openMyProfile() {
            const modal = document.getElementById('myProfileModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeMyProfile() {
            const modal = document.getElementById('myProfileModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>

</html>
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

// 1. Get the student's ID and Control Number
$stmt = $conn->prepare("SELECT id, control_number FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_res = $stmt->get_result()->fetch_assoc();
$student_id = $student_res ? $student_res['id'] : 0;
$_SESSION['control_number'] = $student_res ? $student_res['control_number'] : '';
$stmt->close();

// 2. Check if the student is Approved in ALL 5 research services
$services = ['grammarly_ai', 'ethics', 'human_grammarian', 'librarian', 'statistician'];
$isEligibleForProposal = true;

if ($student_id > 0) {
    foreach ($services as $service) {
        $checkStmt = $conn->prepare("SELECT id FROM $service WHERE student_id = ? AND status = 'Approved' LIMIT 1");
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $isEligibleForProposal = false; // Missing at least one approval
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
    <style>
        /* Invisible Custom Scrollbar for a cleaner look */
        .custom-scrollbar::-webkit-scrollbar { width: 0px; background: transparent; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800 font-sans antialiased selection:bg-blue-100 selection:text-blue-900">

    <header class="fixed top-0 left-0 w-full h-[72px] bg-blue-900/95 backdrop-blur-md text-white shadow-md border-b-[3px] border-b-[#FFC107] z-50 flex items-center justify-between px-6 transition-all">
        <div class="flex items-center gap-3">
            <img src="../images/smcc logo.png" alt="Logo" class="w-10 h-10 object-contain drop-shadow-sm">
            <div class="hidden sm:block flex-col justify-center">
                <h1 class="text-sm font-bold tracking-wide leading-tight">RESEARCH SUPPORT SERVICES</h1>
                <p class="text-[10px] text-blue-200 uppercase tracking-widest font-semibold">Monitoring System</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-[11px] text-blue-200 font-medium uppercase tracking-wider mb-0.5">Welcome, Student</p>
                <p class="text-sm font-bold"><?php echo htmlspecialchars($_SESSION['school_id']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-800 border border-blue-400/50 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </header>

    <div class="flex pt-[72px] min-h-screen">

        <aside class="fixed left-0 top-[72px] w-64 h-[calc(100vh-72px)] bg-white border-r border-gray-200 flex flex-col z-40 overflow-y-auto custom-scrollbar">
            
            <nav class="flex-1 py-5 flex flex-col gap-4">

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Research Services</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    
                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="student_dashboard.php?page=students_rs_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Grammarly & AI
                        </a>
                        <a href="student_dashboard.php?page=students_rs_ethics" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Ethics
                        </a>
                        <a href="student_dashboard.php?page=students_rs_human_grammarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            Human Grammarian
                        </a>
                        <a href="student_dashboard.php?page=students_rs_librarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                            Librarian
                        </a>
                        <a href="student_dashboard.php?page=students_rs_statistician" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            Statistician
                        </a>
                    </div>
                </div>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Communicate</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    
                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="#" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 shadow-sm ml-1"></div>
                            Grammarly & AI
                        </a>
                        <a href="#" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-2 h-2 rounded-full bg-blue-400 shadow-sm ml-1"></div>
                            Ethics
                        </a>
                        <a href="#" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-2 h-2 rounded-full bg-purple-400 shadow-sm ml-1"></div>
                            Human Grammarian
                        </a>
                        <a href="#" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-2 h-2 rounded-full bg-pink-400 shadow-sm ml-1"></div>
                            Librarian
                        </a>
                        <a href="#" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-2 h-2 rounded-full bg-red-400 shadow-sm ml-1"></div>
                            Statistician
                        </a>
                    </div>
                </div>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Transaction</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    
                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="student_dashboard.php?page=student_transaction_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <div class="w-4 h-4 flex items-center justify-center rounded border border-gray-300 text-gray-400 bg-white group-hover:border-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </div>
                            Grammarly & AI Checking
                        </a>
                    </div>
                </div>

            </nav>

            <div class="p-4 border-t border-gray-100 mt-auto flex flex-col gap-1">
                
                <?php if ($isEligibleForProposal): ?>
                <a href="student_dashboard.php?page=student_proposal_certificate" class="flex items-center gap-2.5 px-3 py-2 mb-2 rounded-lg bg-gray-900 text-white font-medium hover:bg-gray-800 transition-colors shadow-sm text-[13px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Proposal Certificate
                </a>
                <?php endif; ?>

                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Settings
                </a>
                
                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-red-600 hover:bg-red-50 transition-colors mt-1">
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

                    default:
                ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 flex flex-col items-center justify-center text-center h-[50vh]">
                            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center mb-5 border border-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2 tracking-tight">Dashboard Overview</h2>
                            <p class="text-[13px] text-gray-500 max-w-md leading-relaxed">Navigate using the sidebar to manage your research submissions, view evaluation feedback, and download approval certificates.</p>
                        </div>
                <?php
                        break;
                }
                ?>

            </div>
        </main>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

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
    </script>
</body>
</html>
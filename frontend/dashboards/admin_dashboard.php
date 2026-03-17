<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

// 1. Get Admin Profile Details
$stmt = $conn->prepare("SELECT school_id, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Invisible Custom Scrollbar for a cleaner look */
        .custom-scrollbar::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        [x-cloak] {
            display: none !important;
        }
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
                <p class="text-[11px] text-blue-200 font-medium uppercase tracking-wider mb-0.5">Welcome, Admin</p>
                <p class="text-sm font-bold"><?php echo htmlspecialchars($admin_profile['school_id'] ?? 'Admin'); ?></p>
            </div>

            <button onclick="openMyAdminProfile()" class="w-10 h-10 rounded-full bg-blue-800 border border-blue-400/50 flex items-center justify-center shadow-inner hover:bg-blue-700 hover:ring-2 hover:ring-blue-300 transition-all focus:outline-none group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200 group-hover:text-white transition-colors" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </header>

    <div class="flex pt-[72px] min-h-screen">

        <aside class="fixed left-0 top-[72px] w-64 h-[calc(100vh-72px)] bg-white border-r border-gray-200 flex flex-col z-40 overflow-y-auto custom-scrollbar">

            <div class="px-5 pt-6 pb-2">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Admin Panel</p>
                <span class="inline-block bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-md text-[11px] tracking-wide border border-blue-100">
                    System Manager
                </span>
            </div>

            <nav class="flex-1 py-4 flex flex-col gap-1.5 px-3">

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Users</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="admin_dashboard.php?page=students" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            View Students
                        </a>
                        <a href="admin_dashboard.php?page=personnel" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            View Personnel
                        </a>
                    </div>
                </div>

                <div x-data="{ open: true }" class="mt-2">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Academics</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="admin_dashboard.php?page=view_departments" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            View Departments
                        </a>
                        <a href="admin_dashboard.php?page=view_courses" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            View Courses
                        </a>
                    </div>
                </div>

                <div x-data="{ open: false }" class="mt-2">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Services</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="admin_dashboard.php?page=analytics_grammarly_ai" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Grammarly & AI
                        </a>
                        <a href="admin_dashboard.php?page=analytics_ethics" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Ethics
                        </a>
                        <a href="admin_dashboard.php?page=analytics_human_grammarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Human Grammarian
                        </a>
                        <a href="admin_dashboard.php?page=analytics_librarian" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                            Librarian
                        </a>
                        <a href="admin_dashboard.php?page=analytics_statistician" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            Statistician
                        </a>
                    </div>
                </div>

                <div class="mt-2 flex flex-col gap-1">
                    <a href="admin_dashboard.php?page=feedback_admin" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                        <div class="w-4 h-4 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8-1.125 0-2.197-.183-3.21-.516L3 21l1.516-5.79C3.183 14.197 3 13.125 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        Feedback Management
                    </a>

                    <div x-data="{ open: false }">
                        <div class="px-5 flex items-center justify-between mb-1.5 mt-3 group cursor-pointer" @click="open = !open">
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Archives</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>

                            <a href="admin_dashboard.php?page=grammarly_ai_files" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                                </svg>
                                Grammarly & AI
                            </a>
                            <a href="admin_dashboard.php?page=ethics_files" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                                </svg>
                                Ethics
                            </a>
                            <a href="admin_dashboard.php?page=human-grammarian_files" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                                </svg>
                                Human Grammarian
                            </a>
                            <a href="admin_dashboard.php?page=librarian_files" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                                </svg>
                                Librarian
                            </a>
                            <a href="admin_dashboard.php?page=statistician_files" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                                </svg>
                                Statistician
                            </a>
                        </div>
                    </div>

                    <a href="admin_dashboard.php?page=manage_transactions" class="mt-2 flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                        <div class="w-4 h-4 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        Manage Transactions
                    </a>
                </div>

                <div class="my-4 border-t border-gray-100"></div>

                <a href="admin_dashboard.php?page=admin_settings" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                    <div class="p-1.5 rounded-lg bg-gray-50 text-gray-400 group-hover:bg-white group-hover:text-gray-600 transition-colors shadow-sm border border-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span>System Settings</span>
                </a>

                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-red-600 hover:bg-red-50 transition-colors mt-1">
                    <div class="p-1.5 rounded-lg bg-red-50 text-red-500 group-hover:bg-red-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2-2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                        </svg>
                    </div>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-6 lg:p-8 min-h-[calc(100vh-72px)]">
            <div id="main-content" class="max-w-7xl mx-auto">

                <?php
                $page = $_GET['page'] ?? 'dashboard';

                switch ($page) {
                    case 'students':
                        include '../students/students_list.php';
                        break;
                    case 'edit_student':
                        include '../dashboards/admin_edit_student.php';
                        break;
                    case 'add_student':
                        include '../students/admin_add_student.php';
                        break;
                    case 'personnel':
                        include '../personnel/personnel_list.php';
                        break;
                    case 'edit_personnel':
                        include '../dashboards/admin_edit_personnel.php';
                        break;
                    case 'add_personnel':
                        include '../personnel/admin_add_personnel.php';
                        break;
                    case 'view_departments':
                        include '../department-courses/view_department.php';
                        break;
                    case 'add_department':
                        include '../department-courses/add_department.php';
                        break;
                    case 'edit_department':
                        include '../department-courses/edit_department.php';
                        break;
                    case 'view_courses':
                        include '../department-courses/view_course.php';
                        break;
                    case 'add_course':
                        include '../department-courses/add_course.php';
                        break;
                    case 'edit_course':
                        include '../department-courses/edit_course.php';
                        break;
                    case 'feedback_admin':
                        include '../feedback/feedback-admin/admin_feedback.php';
                        break;
                    case 'feedback_admin_add':
                        include '../feedback/feedback-admin/admin_add_feedback.php';
                        break;
                    case 'feedback_admin_view':
                        include '../feedback/feedback-admin/admin_view_feedback.php';
                        break;
                    case 'feedback_admin_edit':
                        include '../feedback/feedback-admin/admin_edit_feedback.php';
                        break;
                    case 'admin_settings':
                        include '../settings/admin-settings/admin_settings.php';
                        break;
                    case 'manage_transactions':
                        include '../transactions/manage_transactions.php';
                        break;
                    case 'analytics_ethics':
                        include '../analytics/ethics_analytics.php';
                        break;
                    case 'analytics_grammarly_ai':
                        include '../analytics/grammarly_ai_analytics.php';
                        break;
                    case 'analytics_human_grammarian':
                        include '../analytics/human_grammarian_analytics.php';
                        break;
                    case 'analytics_librarian':
                        include '../analytics/librarian_analytics.php';
                        break;
                    case 'analytics_statistician':
                        include '../analytics/statistician_analytics.php';
                        break;

                    case 'grammarly_ai_files':
                        $archive_title = "Grammarly & AI Checking";
                        $archive_table = "grammarly_ai";
                        include '../archives/archive_list.php';
                        break;
                    case 'ethics_files':
                        $archive_title = "Ethics";
                        $archive_table = "ethics";
                        include '../archives/archive_list.php';
                        break;
                    case 'human-grammarian_files':
                        $archive_title = "Human Grammarian";
                        $archive_table = "human_grammarian";
                        include '../archives/archive_list.php';
                        break;
                    case 'librarian_files':
                        $archive_title = "Librarian";
                        $archive_table = "librarian";
                        include '../archives/archive_list.php';
                        break;
                    case 'statistician_files':
                        $archive_title = "Statistician";
                        $archive_table = "statistician";
                        include '../archives/archive_list.php';
                        break;

                    default:
                ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 flex flex-col items-center justify-center text-center h-[60vh]">
                            <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center mb-5 border border-blue-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2 tracking-tight">Admin Dashboard</h2>
                            <p class="text-[13px] text-gray-500 max-w-md leading-relaxed">Select an option from the sidebar to manage system users, departments, courses, and overall system feedback.</p>
                        </div>
                <?php
                        break;
                }
                ?>

            </div>
        </main>

    </div>

    <div id="profileModalStudent" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[9999] backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold tracking-wide">Student Profile</h3>
                <button onclick="closeProfileStudent()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[80vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800">
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School ID</p>
                        <p class="font-bold text-gray-900" id="p_school_id"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email</p>
                        <p class="font-medium text-gray-800" id="p_email"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                        <p class="font-medium text-gray-800" id="p_department_id"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Course</p>
                        <p class="font-medium text-gray-800" id="p_course_id"></p>
                    </div>
                    <div class="md:col-span-2 border-t border-gray-100 my-1"></div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Control Number</p>
                        <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block" id="p_control_number"></span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Research Leader</p>
                        <p class="font-medium text-gray-800" id="p_research_leader"></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Account Status</p>
                        <span id="p_status" class="inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide bg-gray-100 text-gray-800"></span>
                    </div>
                    <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Thesis Title</p>
                        <p class="font-semibold text-gray-900 leading-snug" id="p_thesis_title"></p>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button onclick="closeProfileStudent()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="masterModalPersonnel" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[9999] backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold tracking-wide">Personnel Profile</h3>
                <button onclick="closeProfilePersonnel()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[80vh]">
                <div class="grid grid-cols-1 gap-y-5 text-sm text-gray-800">
                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-wider mb-0.5">Full Name</p>
                            <p class="font-bold text-xl text-gray-900 leading-none" id="master_p_full_name"></p>
                            <p class="text-blue-600 text-xs font-medium mt-1" id="master_p_email"></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 my-1"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School ID</p>
                            <p class="font-bold text-gray-900" id="master_p_school_id"></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Service Role</p>
                            <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block break-words" id="master_p_service_role"></span>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Assigned Department</p>
                        <p class="font-semibold text-gray-900 leading-snug" id="master_p_dept"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Account Status</p>
                        <span id="master_p_status" class="inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide bg-gray-100 text-gray-800"></span>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button onclick="closeProfilePersonnel()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="myAdminProfileModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform">
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold tracking-wide flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    My Profile
                </h3>
                <button onclick="closeMyAdminProfile()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
            </div>

            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-1 gap-y-5 text-sm text-gray-800">

                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-wider mb-0.5">Admin School ID</p>
                            <p class="font-bold text-xl text-gray-900 leading-none"><?php echo htmlspecialchars($admin_profile['school_id'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-1"></div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">System Role</p>
                            <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block break-words">
                                System Administrator
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Account Status</p>
                            <span class="font-bold text-green-700 bg-green-50 px-2.5 py-0.5 rounded border border-green-100 inline-block break-words">
                                Active
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email Address</p>
                        <p class="font-semibold text-gray-900 leading-snug"><?php echo htmlspecialchars($admin_profile['email'] ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button onclick="closeMyAdminProfile()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
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

        // Admin Profile Modal Functions
        function openMyAdminProfile() {
            const modal = document.getElementById('myAdminProfileModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeMyAdminProfile() {
            const modal = document.getElementById('myAdminProfileModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Student Profile Modal Functions
        window.openProfileStudent = function(data) {
            document.getElementById("p_school_id").textContent = data.school_id || "-";
            document.getElementById("p_email").textContent = data.email || "-";
            document.getElementById("p_department_id").textContent = data.department_name || data.department || "-";
            document.getElementById("p_course_id").textContent = data.course_name || data.course || "-";
            document.getElementById("p_control_number").textContent = data.control_number || "-";
            document.getElementById("p_research_leader").textContent = data.research_leader || "-";
            document.getElementById("p_status").textContent = data.status || "-";
            document.getElementById("p_thesis_title").textContent = data.thesis_title || "-";

            const modal = document.getElementById("profileModalStudent");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        window.closeProfileStudent = function() {
            const modal = document.getElementById("profileModalStudent");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        // 3. Personnel Profile Modal Functions (MASTER)
        window.openProfilePersonnel = function(data) {
            document.getElementById("master_p_school_id").textContent = data.school_id || data.School_ID || "-";
            document.getElementById("master_p_email").textContent = data.email || data.Email || "-";
            document.getElementById("master_p_full_name").textContent = data.full_name || data.name || "-";
            document.getElementById("master_p_service_role").textContent = data.service_role || data.role || "-";
            document.getElementById("master_p_status").textContent = data.status || data.Status || "-";

            // Check if department_name exists, otherwise fallback to "Global Service"
            document.getElementById("master_p_dept").textContent = data.department_name || data.department || "Global Service";

            const modalp = document.getElementById("masterModalPersonnel");
            modalp.classList.remove("hidden");
            modalp.classList.add("flex");
        }

        window.closeProfilePersonnel = function() {
            const modalp = document.getElementById("masterModalPersonnel");
            modalp.classList.add("hidden");
            modalp.classList.remove("flex");
        }
    </script>

</body>

</html>
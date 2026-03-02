<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <header class="w-full bg-blue-900 text-white shadow-md border-b-[3px] border-b-[#FFC107]">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <img src="../images/smcc logo.png" alt="Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-sm font-semibold leading-tight">RESEARCH SUPPORT SERVICES AND MONITORING SYSTEM</h1>
                </div>
            </div>

            <div class="text-right">
                <p class="text-xs text-blue-100">Welcome Student,</p>
                <p class="text-xs font-semibold"><?php echo $_SESSION['school_id']; ?></p>
            </div>
        </div>
    </header>


    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-5 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Student Panel</h3>
                <p class="text-xs text-gray-500">Student Research Information RSSMS</p>
            </div>

            <nav class="p-4 text-sm">

                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-6 8h4m-6 0h.01M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>Research Services</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="student_dashboard.php?page=students_rs_grammarly_ai" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Grammarly & AI Checking</a>
                        <a href="student_dashboard.php?page=students_rs_ethics" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Ethics</a>
                        <a href="student_dashboard.php?page=students_rs_human_grammarian" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Human Grammarian</a>
                        <a href="student_dashboard.php?page=students_rs_librarian" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Librarian</a>
                        <a href="student_dashboard.php?page=students_rs_statistician" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Statistician</a>
                    </div>
                </div>


                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h8M8 14h5m9-2a9 9 0 11-4.5-7.8L21 3l-1.2 6.5A8.96 8.96 0 0117 12z" />
                            </svg>

                            <span>Communicate</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Grammarly & AI Checking</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Ethics</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Human Grammarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Librarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Statistician</a>
                    </div>
                </div>

                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16l-4-4m0 0l4-4m-4 4h18M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                            <span>Transaction</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="student_dashboard.php?page=student_transaction_grammarly_ai" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Grammarly & AI Checking</a>
                    </div>
                </div>

                <hr class="my-4">

                <a href="../auth/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-red-600 hover:bg-red-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <div id="main-content">

                <?php
                require_once "../../backend/config/database.php";

                $page = $_GET['page'] ?? 'dashboard';

                switch ($page) {
                    case 'students_rs_grammarly_ai':
                        include '../students/student-research-services/grammarly_ai_checking.php';
                        break;

                    case 'students_rs_ethics':
                        include '../students/student-research-services/ethics.php';
                        break;

                    case 'students_rs_human_grammarian':
                        include '../students/student-research-services/human_grammarian.php';
                        break;

                    case 'students_rs_librarian':
                        include '../students/student-research-services/librarian.php';
                        break;

                    case 'students_rs_statistician':
                        include '../students/student-research-services/statistician.php';
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
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold text-gray-700 mb-2">Student Dashboard</h2>
                            <p class="text-sm text-gray-500">Select an option from the sidebar to begin.</p>
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
<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
                <p class="text-xs text-blue-100">Welcome,</p>
                <p class="text-xs font-semibold"><?php echo $_SESSION['school_id']; ?></p>
            </div>
        </div>
    </header>


    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-5 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Admin Panel</h3>
                <p class="text-xs text-gray-500">Manage the RSSMS</p>
            </div>

            <nav class="p-4 text-sm">

                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 019 15h6a4 4 0 013.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Manage User Accounts</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="admin_dashboard.php?page=students" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">View Students Account</a>
                        <a href="admin_dashboard.php?page=personnel" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">View Personnel</a>
                    </div>
                </div>

                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z" />
                            </svg>
                            <span>Department and Courses</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="admin_dashboard.php?page=view_departments" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">View Department</a>
                        <a href="admin_dashboard.php?page=view_courses" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">View Courses</a>
                    </div>
                </div>

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
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Grammarly & AI Checking</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Ethics</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Human Grammarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Librarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Statistician</a>
                    </div>
                </div>

                <a href="admin_dashboard.php?page=feedback_admin" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8-1.125 0-2.197-.183-3.21-.516L3 21l1.516-5.79C3.183 14.197 3 13.125 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>Feedback</span>
                </a>

                <div x-data="{ open: false }" class="mb-1">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h6a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h5z" />
                            </svg>
                            <span>Uploaded Files</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-1 ml-7 flex flex-col gap-1" x-cloak>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Students File</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Grammarly & AI Checking</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Ethics</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Human Grammarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Librarian</a>
                        <a href="#" class="px-3 py-1 text-gray-700 rounded-lg hover:bg-blue-100 hover:text-blue-900 transition">Statistician</a>
                    </div>
                </div>

                <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm4 4h8v8H8V8z" />
                    </svg>
                    <span>Manage Transactions</span>
                </a>

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

                    default:
                ?>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-semibold text-gray-700 mb-2">Dashboard</h2>
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

        window.openProfileStudent = function(data) {
            document.getElementById("p_school_id").textContent = data.school_id || "-";
            document.getElementById("p_email").textContent = data.email || "-";
            document.getElementById("p_thesis_title").textContent = data.thesis_title || "-";
            document.getElementById("p_control_number").textContent = data.control_number || "-";
            document.getElementById("p_research_leader").textContent = data.research_leader || "-";
            document.getElementById("p_status").textContent = data.status || "-";
            document.getElementById("p_department_id").textContent = data.department_name || "-";
            document.getElementById("p_course_id").textContent = data.course_name || "-";

            const modal = document.getElementById("profileModalStudent");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        window.closeProfileStudent = function() {
            const modal = document.getElementById("profileModalStudent");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        window.openProfilePersonnel = function(data) {
            document.getElementById("p_school_id").textContent = data.school_id || "-";
            document.getElementById("p_email").textContent = data.email || "-";
            document.getElementById("p_full_name").textContent = data.full_name || "-";
            document.getElementById("p_service_role").textContent = data.service_role || "-";
            document.getElementById("p_status").textContent = data.status || "-";
            document.getElementById("p_department_id").textContent = data.department_name || "-";


            const modalp = document.getElementById("profileModalPersonnel");
            modalp.classList.remove("hidden");
            modalp.classList.add("flex");
        }

        window.closeProfilePersonnel = function() {
            const modalp = document.getElementById("profileModalPersonnel");
            modalp.classList.add("hidden");
            modalp.classList.remove("flex");
        }
    </script>

</body>

</html>
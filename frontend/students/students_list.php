<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

// 1. Fetch Global Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// 2. UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024; // The permanent year your system went live
$current_calendar_year = (int)date("Y"); 

// Generate up to 2 years into the future
$max_year = $current_calendar_year + 2; 

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}
?>

<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Student Accounts</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_student"
            class="bg-blue-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            + Add Student
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center">

        <input
            type="text"
            id="studentSearch"
            placeholder="Search by School ID..."
            class="w-full md:w-1/3 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

        <select id="syFilter" class="w-full md:w-1/4 border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All School Years</option>
            <?php foreach($generated_years as $year): ?>
                <option value="<?= htmlspecialchars($year) ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?= htmlspecialchars($year) ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex gap-2 bg-gray-50 p-1 rounded-lg border border-gray-200 ml-auto">
            <button onclick="setStatusFilter('All')" id="btn-status-All"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white shadow-sm text-blue-700">
                All
            </button>
            <button onclick="setStatusFilter('Pending')" id="btn-status-Pending"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 hover:text-gray-700">
                Pending
            </button>
            <button onclick="setStatusFilter('Approved')" id="btn-status-Approved"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 hover:text-gray-700">
                Approved
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">School ID</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="studentTableBody" class="divide-y divide-gray-100"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="profileModalStudent" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Student Profile</h3>
            <button onclick="closeProfileStudent()" class="text-white hover:text-gray-200 text-sm">✕</button>
        </div>
        <div class="p-6 overflow-y-auto">
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
                    <span id="p_status" class="inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide"></span>
                </div>
                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Thesis Title</p>
                    <p class="font-semibold text-gray-900 leading-snug" id="p_thesis_title"></p>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button onclick="closeProfileStudent()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let currentStatusFilter = 'All';
    let searchTimeout;

    function fetchStudents(page = 1) {
        currentPage = page;
        const search = document.getElementById('studentSearch').value;
        const schoolYear = document.getElementById('syFilter').value; 

        fetch(`../../backend/ajax/fetch_students.php?p=${page}&search=${encodeURIComponent(search)}&status=${currentStatusFilter}&sy=${encodeURIComponent(schoolYear)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('studentTableBody');
                tbody.innerHTML = '';

                if (data.students.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p class="font-medium text-gray-500">No Student Accounts Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.students.forEach(student => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 transition";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800"></td>
                        <td class="px-6 py-4 text-center"></td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 px-4 py-1.5 hover:underline text-xs"
                                onclick='openProfileStudent(${JSON.stringify(student).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <a href="../dashboards/admin_dashboard.php?page=edit_student&id=${student.id}"
                                class="text-blue-700 px-4 py-1.5 hover:underline text-xs">
                                Update
                            </a>
                        </td>
                    `;

                    row.children[1].textContent = student.school_id;

                    if (student.status === 'Pending') {
                        row.children[2].innerHTML = `
                            <form action="../../backend/actions/approve_user.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="${student.id}">
                                <button type="submit" class="bg-blue-50 text-blue-700 font-bold px-3 py-1.5 rounded-md text-xs hover:bg-blue-100 transition shadow-sm border border-blue-100">
                                    Approve
                                </button>
                            </form>
                        `;
                    } else {
                        row.children[2].innerHTML = `
                            <span class="bg-green-50 text-green-700 font-bold px-3 py-1.5 rounded-md text-xs border border-green-100">
                                Approved
                            </span>
                        `;
                    }

                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);

                const totalRows = data.totalRows || 0;
                const startRecord = (totalRows > 0) ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                document.getElementById('recordInfo').textContent =
                    totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Students` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchStudents(${currentPage - 1})" class="px-3 py-1 border border-gray-200 rounded-md text-xs text-gray-600 hover:bg-gray-50 transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchStudents(${i})" class="px-3 py-1 text-xs border border-gray-200 rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 hover:bg-gray-50'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchStudents(${currentPage + 1})" class="px-3 py-1 border border-gray-200 text-gray-600 text-xs rounded-md hover:bg-gray-50 transition shadow-sm">Next</button>`;
        }
    }

    function setStatusFilter(status) {
        currentStatusFilter = status;
        
        ['All', 'Pending', 'Approved'].forEach(btn => {
            const el = document.getElementById('btn-status-' + btn);
            if (btn === status) {
                el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white shadow-sm text-blue-700";
            } else {
                el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 hover:text-gray-700";
            }
        });

        fetchStudents(1);
    }

    document.getElementById('syFilter').addEventListener('change', () => fetchStudents(1));

    document.getElementById('studentSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchStudents(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchStudents(1));
</script>
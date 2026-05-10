<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

// 1. Fetch Global Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// 2. Fetch departments for the filter dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

// 3. UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024; // The permanent year your system went live
$current_calendar_year = (int)date("Y"); 

// Generate up to 2 years into the future
$max_year = $current_calendar_year + 2; 

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border transition-colors">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Student Accounts</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_student"
            class="bg-blue-900 dark:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition">
            + Add Student
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center">

        <!-- FIX: Updated Placeholder -->
        <input
            type="text"
            id="studentSearch"
            placeholder="Search by Control Number..."
            class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">

        <select id="deptFilter" class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Departments</option>
            <?php while($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="syFilter" class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
            <option value="All">All School Years</option>
            <?php foreach($generated_years as $year): ?>
                <option value="<?= htmlspecialchars($year) ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?= htmlspecialchars($year) ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex gap-2 bg-gray-50 dark:bg-warmdark-bg p-1 rounded-lg border border-gray-200 dark:border-warmdark-border ml-auto transition-colors">
            <button onclick="setStatusFilter('All')" id="btn-status-All"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400">
                All
            </button>
            <button onclick="setStatusFilter('Pending')" id="btn-status-Pending"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Pending
            </button>
            <button onclick="setStatusFilter('Approved')" id="btn-status-Approved"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Approved
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-warmdark-border">
                <!-- FIX: Added whitespace-nowrap and updated ID to Control Number -->
                <tr>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap">#</th>
                    <th class="px-6 py-4 font-semibold whitespace-nowrap">Control Number</th>
                    <th class="px-6 py-4 font-semibold whitespace-nowrap">Department</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap">Status</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap">Profile</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap">Action</th>
                </tr>
            </thead>
            <tbody id="studentTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
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
        const dept = document.getElementById('deptFilter').value;

        fetch(`../../backend/ajax/fetch_students.php?p=${page}&search=${encodeURIComponent(search)}&status=${currentStatusFilter}&sy=${encodeURIComponent(schoolYear)}&dept=${encodeURIComponent(dept)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('studentTableBody');
                tbody.innerHTML = '';

                if (data.students.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No Student Accounts Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.students.forEach(student => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    let deptName = student.department_name ? student.department_name : 'N/A';
                    
                    let statusHtml = '';
                    if (student.status === 'Pending') {
                        statusHtml = `
                            <form action="../../backend/actions/approve_user.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="${student.id}">
                                <button type="submit" class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-bold px-3 py-1.5 rounded-md text-xs hover:bg-blue-100 dark:hover:bg-blue-900/50 transition shadow-sm border border-blue-100 dark:border-blue-900/50 inline-block w-[80px]">
                                    Approve
                                </button>
                            </form>
                        `;
                    } else {
                        statusHtml = `
                            <span class="bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 font-bold px-3 py-1.5 rounded-md text-xs border border-green-100 dark:border-green-500/20 inline-block w-[80px] text-center">
                                Approved
                            </span>
                        `;
                    }

                    // FIX: Replaced ID with Control Number, and formatted buttons perfectly
                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 whitespace-nowrap">${counter++}.</td>
                        <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">${student.control_number || 'No Control No.'}</td>
                        <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 truncate max-w-[200px]" title="${deptName}">${deptName}</td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">${statusHtml}</td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <button class="text-blue-700 dark:text-blue-400 px-4 py-1.5 hover:underline text-xs font-bold"
                                onclick='openProfileStudent(${JSON.stringify(student).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <a href="../dashboards/admin_dashboard.php?page=edit_student&id=${student.id}"
                                class="bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-warmdark-border hover:bg-gray-200 dark:hover:bg-warmdark-hover px-4 py-1.5 rounded-lg text-xs font-bold transition-colors inline-block text-center w-[80px]">
                                Update
                            </a>
                        </td>
                    `;

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
            container.innerHTML += `<button onclick="fetchStudents(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchStudents(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchStudents(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    function setStatusFilter(status) {
        currentStatusFilter = status;
        
        ['All', 'Pending', 'Approved'].forEach(btn => {
            const el = document.getElementById('btn-status-' + btn);
            if (btn === status) {
                el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400";
            } else {
                el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200";
            }
        });

        fetchStudents(1);
    }

    // Attach event listeners to filters
    ['syFilter', 'deptFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchStudents(1));
    });

    document.getElementById('studentSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchStudents(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchStudents(1));
</script>
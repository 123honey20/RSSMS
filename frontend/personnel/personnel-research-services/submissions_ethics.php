<?php
// Fetch Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024;
$current_calendar_year = (int)date("Y"); 

// Generate up to 2 years into the future
$max_year = $current_calendar_year + 2; 

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}

// NEW: Fetch assigned departments from junction table for this specific personnel
$user_id = $_SESSION['user'];
$assigned_depts = [];

$deptStmt = $conn->prepare("
    SELECT d.id, d.name 
    FROM personnel_departments pd
    JOIN departments d ON pd.department_id = d.id
    WHERE pd.user_id = ?
    ORDER BY d.name ASC
");
$deptStmt->bind_param("i", $user_id);
$deptStmt->execute();
$resDepts = $deptStmt->get_result();
while ($d = $resDepts->fetch_assoc()) {
    $assigned_depts[] = $d;
}
$deptStmt->close();
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm min-h-[80vh] border border-transparent dark:border-warmdark-border transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Student Submissions</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search by Control Number..."
            class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">

        <select id="syFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">
            <option value="All">All School Years</option>
            <?php foreach($generated_years as $year): ?>
                <option value="<?php echo $year; ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?php echo $year; ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="statusFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">
            <option value="All">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>

        <select id="personnelDeptFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm text-gray-700 dark:text-gray-200 font-medium transition-colors">
            <option value="All">All Handled Depts</option>
            <?php foreach($assigned_depts as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left border-collapse text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">No.</th>
                    <th class="px-6 py-4 font-semibold">Control No.</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Round</th>
                    <th class="px-6 py-4 font-semibold text-center">Student Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchSubmissions(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value; 
        const sy = document.getElementById('syFilter').value; 
        const pDept = document.getElementById('personnelDeptFilter').value; // NEW

        fetch(`../../backend/ajax/fetch_ethics_submissions.php?p=${page}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&sy=${encodeURIComponent(sy)}&dept=${encodeURIComponent(pDept)}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if (data.submissions.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No Submissions Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.submissions.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    let statusBadge = '';
                    if (row.status === 'Approved') {
                        statusBadge = `<span class="text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/20 border border-green-100 dark:border-green-500/30 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Approved</span>`;
                    } else if (row.status === 'Rejected') {
                        statusBadge = `<span class="text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-900/50 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Rejected</span>`;
                    } else {
                        statusBadge = `<span class="text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-100 dark:border-yellow-900/50 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Pending</span>`;
                    }

                    let actionButton = '';
                    if (row.status === 'Approved' || row.status === 'Rejected') {
                        actionButton = `<button onclick="viewSubmissionWithComments(${row.id})" class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border px-4 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-warmdark-hover transition font-bold text-xs shadow-sm">Review Submission</button>`;
                    } else {
                        actionButton = `<button onclick="loadProcess(${row.id})" class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border px-4 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-warmdark-hover transition font-bold text-xs shadow-sm">Process</button>`;
                    }

                    tr.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200">${row.control_number}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">${row.round}</td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 dark:text-blue-400 px-4 py-1.5 rounded-lg hover:underline text-xs"
                                onclick='openProfileStudent(${JSON.stringify(row).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            ${actionButton}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination(data.totalPages, data.currentPage);
                const totalRows = data.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Submissions` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchSubmissions(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchSubmissions(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchSubmissions(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    // NEW: Added personnelDeptFilter to the listener array
    ['statusFilter', 'syFilter', 'personnelDeptFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchSubmissions(1));
    });

    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchSubmissions(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchSubmissions(1));
</script>
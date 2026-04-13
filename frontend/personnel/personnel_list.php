<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

// Fetch departments for the filter dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border min-h-[80vh] transition-colors duration-200">

    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Personnel Accounts</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_personnel"
            class="bg-blue-900 dark:bg-blue-800 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition font-medium shadow-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add Personnel
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center">

        <input type="text" id="personnelSearch" placeholder="Search by School ID..."
            class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">

        <select id="deptFilter" class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Departments</option>
            <?php while($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="roleFilter" class="w-full md:w-1/4 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Service Roles</option>
            <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
            <option value="Human Grammarian">Human Grammarian</option>
            <option value="Statistician">Statistician</option>
            <option value="Librarian">Librarian</option>
            <option value="Ethics">Ethics</option>
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
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">School ID</th>
                    <th class="px-6 py-4 font-semibold max-w-xs">Department</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="personnelTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<script>
    let currentPage = 1;
    let currentStatusFilter = 'All';
    let searchTimeout;

    function fetchPersonnel(page = 1) {
        currentPage = page;
        const search = document.getElementById('personnelSearch').value;
        const dept = document.getElementById('deptFilter').value;
        const role = document.getElementById('roleFilter').value;

        fetch(`../../backend/ajax/fetch_personnel.php?p=${page}&search=${encodeURIComponent(search)}&dept=${encodeURIComponent(dept)}&role=${encodeURIComponent(role)}&status=${encodeURIComponent(currentStatusFilter)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('personnelTableBody');
                tbody.innerHTML = '';

                if (data.personnel.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No Personnel Accounts Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.personnel.forEach(person => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200"></td>
                        <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 truncate max-w-xs" title=""></td>
                        <td class="px-6 py-4 text-center"></td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 dark:text-blue-400 px-4 py-1.5 hover:underline text-xs"
                                onclick='openProfilePersonnel(${JSON.stringify(person).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <a href="../dashboards/admin_dashboard.php?page=edit_personnel&id=${person.id}"
                                class="text-blue-700 dark:text-blue-400 px-4 py-1.5 hover:underline text-xs">
                                Update
                            </a>
                        </td>
                    `;

                    // Inject School ID
                    row.children[1].textContent = person.school_id;
                    
                    // Inject Department Name(s) logically (REMOVED GLOBAL GRAMMARLY OVERRIDE)
                    let displayDept = person.department_name;
                    if (!displayDept || displayDept.trim() === '') {
                        displayDept = 'Unassigned';
                    }
                    row.children[2].textContent = displayDept;
                    row.children[2].title = displayDept; // Hover tooltip for long lists

                    // Inject Status Logic
                    if (person.status === 'Pending') {
                        row.children[3].innerHTML = `
                            <form action="../../backend/actions/approve_user.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="${person.id}">
                                <button type="submit" class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-bold px-3 py-1.5 rounded-md text-xs hover:bg-blue-100 dark:hover:bg-blue-900/50 transition shadow-sm border border-blue-100 dark:border-blue-900/50">
                                    Approve
                                </button>
                            </form>
                        `;
                    } else {
                        row.children[3].innerHTML = `
                            <span class="bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 font-bold px-3 py-1.5 rounded-md text-xs border border-green-100 dark:border-green-500/20">
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
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Personnel` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchPersonnel(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchPersonnel(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchPersonnel(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
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

        fetchPersonnel(1);
    }

    ['deptFilter', 'roleFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchPersonnel(1));
    });

    document.getElementById('personnelSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchPersonnel(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchPersonnel(1));
</script>
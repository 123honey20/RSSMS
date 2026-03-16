<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

// Fetch departments for the filter dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Personnel Accounts</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_personnel"
            class="bg-blue-900 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-blue-800 transition font-medium shadow-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add Personnel
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center">

        <input type="text" id="personnelSearch" placeholder="Search by School ID..."
            class="w-full md:w-1/4 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

        <select id="deptFilter" class="w-full md:w-1/4 border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All Departments</option>
            <?php while($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="roleFilter" class="w-full md:w-1/4 border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All Service Roles</option>
            <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
            <option value="Human Grammarian">Human Grammarian</option>
            <option value="Statistician">Statistician</option>
            <option value="Librarian">Librarian</option>
            <option value="Ethics">Ethics</option>
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
        <table class="w-full text-sm text-left text-gray-600 border-collapse">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">School ID</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="personnelTableBody" class="divide-y divide-gray-100"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="profileModalPersonnel" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 ease-out">
        <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold tracking-wide">Personnel Profile</h3>
            <button onclick="closeProfilePersonnel()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800">
                
                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200 mb-2 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center shrink-0 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-0.5">Full Name</p>
                        <p class="font-bold text-lg text-gray-900 leading-none" id="p_full_name"></p>
                    </div>
                </div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School ID</p>
                    <p class="font-bold text-gray-900" id="p_school_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email</p>
                    <p class="font-medium text-gray-800 break-all" id="p_email"></p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 my-1"></div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                    <p class="font-medium text-gray-800" id="p_department_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Service Role</p>
                    <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block" id="p_service_role"></span>
                </div>

                <div class="md:col-span-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Account Status</p>
                    <span id="p_status" class="inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide"></span>
                </div>

            </div>
            <div class="mt-8 flex justify-end">
                <button onclick="closeProfilePersonnel()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">Close</button>
            </div>
        </div>
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
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <p class="font-medium text-gray-500">No Personnel Accounts Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.personnel.forEach(person => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 transition";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800"></td>
                        <td class="px-6 py-4 text-center"></td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 px-4 py-1.5 hover:underline text-xs"
                                onclick='openProfilePersonnel(${JSON.stringify(person).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <a href="../dashboards/admin_dashboard.php?page=edit_personnel&id=${person.id}"
                                class="text-blue-700 px-4 py-1.5 hover:underline text-xs">
                                Update
                            </a>
                        </td>
                    `;

                    row.children[1].textContent = person.school_id;

                    if (person.status === 'Pending') {
                        row.children[2].innerHTML = `
                            <form action="../../backend/actions/approve_user.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="${person.id}">
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
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Personnel` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchPersonnel(${currentPage - 1})" class="px-3 py-1 border border-gray-200 rounded-md text-xs text-gray-600 hover:bg-gray-50 transition shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchPersonnel(${i})" class="px-3 py-1 text-xs border border-gray-200 rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 hover:bg-gray-50'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchPersonnel(${currentPage + 1})" class="px-3 py-1 border border-gray-200 text-gray-600 text-xs rounded-md hover:bg-gray-50 transition shadow-sm">Next</button>`;
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

        fetchPersonnel(1);
    }

    function openProfilePersonnel(data) {
        document.getElementById('p_full_name').textContent = data.full_name || 'N/A';
        document.getElementById('p_school_id').textContent = data.school_id || 'N/A';
        document.getElementById('p_email').textContent = data.email || 'N/A';
        document.getElementById('p_department_id').textContent = data.department_name || 'Global Service (All Departments)';
        document.getElementById('p_service_role').textContent = data.service_role || 'N/A';
        
        const statusEl = document.getElementById('p_status');
        statusEl.textContent = data.status;
        if(data.status === 'Approved') {
            statusEl.className = "inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide bg-green-100 text-green-700";
        } else {
            statusEl.className = "inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide bg-yellow-100 text-yellow-700";
        }

        const modal = document.getElementById('profileModalPersonnel');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeProfilePersonnel() {
        const modal = document.getElementById('profileModalPersonnel');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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
<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

require_once "../../backend/config/database.php";
// Fetch departments for the new filter
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border min-h-[80vh] transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Statistician Personnel Requests</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review student applications for Statistician personnel and verify contracts.</p>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center">
        <input type="text" id="reqSearchInput" placeholder="Search Control No..."
            class="w-full md:w-1/3 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">

        <select id="reqDeptFilter" class="w-full md:w-1/3 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Departments</option>
            <?php while ($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <div class="flex gap-2 bg-gray-50 dark:bg-warmdark-bg p-1 rounded-lg border border-gray-200 dark:border-warmdark-border ml-auto transition-colors">
            <button onclick="setReqStatusFilter('All')" id="btn-status-All"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400">
                All
            </button>
            <button onclick="setReqStatusFilter('Pending')" id="btn-status-Pending"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Pending
            </button>
            <button onclick="setReqStatusFilter('Approved')" id="btn-status-Approved"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Approved
            </button>
            <button onclick="setReqStatusFilter('Rejected')" id="btn-status-Rejected"
                class="px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                Rejected
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold">Student Control No.</th>
                    <th class="px-6 py-4 font-semibold">Requested Personnel</th>
                    <th class="px-6 py-4 font-semibold text-center">Contract</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="requestsTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="reqPaginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
    </div>
</div>

<div id="processRequestModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[9999] backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 border border-transparent dark:border-warmdark-border">
        <div class="bg-gradient-to-r from-blue-700 to-blue-900 dark:from-warmdark-bg dark:to-warmdark-bg text-white px-6 py-4 flex items-center justify-between dark:border-b dark:border-warmdark-border">
            <h3 class="text-lg font-semibold tracking-wide">Process Application</h3>
            <button onclick="closeReqModal()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
        </div>

        <form action="../../backend/actions/process_application_action.php" method="POST" class="p-6">
            <input type="hidden" name="application_id" id="modal_req_app_id">

            <div class="mb-5 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border">
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Student Control No.</p>
                <p class="font-bold text-gray-900 dark:text-gray-100" id="modal_req_student_control"></p>
                <div class="mt-3">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Service Type</p>
                    <span class="inline-flex items-center gap-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50 px-2.5 py-1 rounded-md text-xs font-bold" id="modal_req_service_type">Statistician</span>
                </div>
            </div>

            <div class="mb-5">
                <p class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Contract Verification</p>
                <a id="modal_req_contract_link" href="#" target="_blank" class="hidden items-center justify-center gap-2 w-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50 py-3 rounded-lg font-bold text-sm hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Uploaded Contract
                </a>
                <p id="modal_req_no_contract" class="hidden text-center text-sm text-gray-500 dark:text-gray-400 italic py-3 border border-gray-200 dark:border-warmdark-border rounded-lg bg-gray-50 dark:bg-warmdark-bg">No contract uploaded</p>
            </div>

            <div class="mb-8">
                <label class="block text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Officially Assign Personnel</label>
                <select name="assigned_personnel_id" id="modal_req_personnel_select" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors appearance-none">
                    <option value="">Loading available personnel...</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-2 italic">* Defaults to requested personnel. If rejecting, you can leave this blank.</p>
            </div>

            <div id="modal_action_buttons" class="flex items-center gap-3 border-t border-gray-100 dark:border-warmdark-border pt-4">
                <button type="submit" name="action" value="Reject" class="w-1/3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-800/50 py-2.5 rounded-lg font-bold text-sm transition-colors">
                    Reject
                </button>
                <button type="submit" name="action" value="Approve" class="w-2/3 bg-green-600 text-white hover:bg-green-700 py-2.5 rounded-lg font-bold text-sm shadow-md transition-colors">
                    Approve & Assign
                </button>
            </div>

            <div id="modal_close_only_button" class="hidden border-t border-gray-100 dark:border-warmdark-border pt-4">
                <button type="button" onclick="closeReqModal()" class="w-full bg-gray-100 dark:bg-warmdark-hover text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-warmdark-border border border-gray-200 dark:border-warmdark-border py-2.5 rounded-lg font-bold text-sm transition-colors">
                    Close Review
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var reqCurrentPage = 1;
    var reqStatusFilter = 'All';
    var reqSearchTimer;

    window.loadAdminRequests = function(page = 1) {
        reqCurrentPage = page;
        var searchInput = document.getElementById('reqSearchInput');
        var deptFilter = document.getElementById('reqDeptFilter'); 

        var search = searchInput ? searchInput.value : '';
        var dept = deptFilter ? deptFilter.value : 'All';

        // Hardcode 'Statistician' into the fetch URL instead of using a filter dropdown
        fetch(`../../backend/ajax/fetch_personnel_requests.php?p=${page}&search=${encodeURIComponent(search)}&service=Statistician&status=${encodeURIComponent(reqStatusFilter)}&dept=${encodeURIComponent(dept)}`)
            .then(res => res.json())
            .then(data => {
                var tbody = document.getElementById('requestsTableBody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (!data.requests || data.requests.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No requests found.</td></tr>`;
                    var pagination = document.getElementById('reqPaginationContainer');
                    if (pagination) pagination.innerHTML = '';
                    return;
                }

                data.requests.forEach(req => {
                    var row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    var contractHtml = req.contract_file_path && req.contract_file_path !== 'null' ?
                        `<a href="../../uploads/contracts/${req.contract_file_path}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-bold">View File</a>` :
                        `<span class="text-gray-400 dark:text-gray-500 text-xs italic">None</span>`;

                    var statusClass = req.status === 'Pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' : (req.status === 'Approved' ? 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400');

                    // Action Button (Process vs Review)
                    var actionHtml = req.status === 'Pending' ?
                        `<button type="button" onclick="openReqModal(${req.id}, ${req.department_id || 0}, '${req.service_type}', ${req.requested_personnel_id}, '${req.contract_file_path || ''}', '${req.control_number}', '${req.status}', ${req.assigned_personnel_id || 'null'})" class="bg-blue-600 dark:bg-blue-700 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-blue-700 dark:hover:bg-blue-600 transition shadow-sm">Process</button>` :
                        `<button type="button" onclick="openReqModal(${req.id}, ${req.department_id || 0}, '${req.service_type}', ${req.requested_personnel_id}, '${req.contract_file_path || ''}', '${req.control_number}', '${req.status}', ${req.assigned_personnel_id || 'null'})" class="bg-gray-500 dark:bg-gray-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-gray-600 dark:hover:bg-gray-500 transition shadow-sm">Review</button>`;

                    row.innerHTML = `
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-200">${req.control_number}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${req.department_name || 'Global'}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">${req.requested_name}</td>
                        <td class="px-6 py-4 text-center">${contractHtml}</td>
                        <td class="px-6 py-4 text-center"><span class="px-2 py-1 rounded text-[11px] font-bold tracking-wider border border-transparent ${statusClass}">${req.status}</span></td>
                        <td class="px-6 py-4 text-center">${actionHtml}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(err => console.error("Error fetching requests:", err));
    };

    window.setReqStatusFilter = function(status) {
        reqStatusFilter = status;
        // Added 'All' to array
        ['All', 'Pending', 'Approved', 'Rejected'].forEach(btn => {
            var el = document.getElementById('btn-status-' + btn);
            if (el) {
                if (btn === status) {
                    el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400";
                } else {
                    el.className = "px-4 py-1.5 rounded-md text-xs font-bold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200";
                }
            }
        });

        if (typeof window.loadAdminRequests === 'function') {
            window.loadAdminRequests(1);
        }
    };

    window.openReqModal = function(appId, deptId, serviceType, reqPersonnelId, contractPath, controlNo, status, assignedPersonnelId) {
        document.getElementById('modal_req_app_id').value = appId;
        document.getElementById('modal_req_student_control').textContent = controlNo;

        var contractLink = document.getElementById('modal_req_contract_link');
        var noContract = document.getElementById('modal_req_no_contract');

        if (contractPath && contractPath !== 'null' && contractPath !== '') {
            contractLink.href = "../../uploads/contracts/" + contractPath;
            contractLink.classList.remove('hidden');
            contractLink.classList.add('flex');
            noContract.classList.add('hidden');
        } else {
            contractLink.classList.add('hidden');
            contractLink.classList.remove('flex');
            noContract.classList.remove('hidden');
        }

        var select = document.getElementById('modal_req_personnel_select');
        select.innerHTML = '<option value="">Loading available personnel...</option>';
        select.disabled = (status !== 'Pending'); // Lock dropdown if reviewing

        fetch(`../../backend/ajax/get_personnel_for_assignment.php?dept=${deptId}&service=${encodeURIComponent(serviceType)}`)
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '';
                if (!data || data.length === 0) {
                    select.innerHTML = '<option value="">No personnel available for this department</option>';
                } else {
                    data.forEach(p => {
                        let isSelected = '';
                        let labelSuffix = (p.id == reqPersonnelId) ? ' (Requested)' : '';

                        if (status === 'Pending') {
                            isSelected = (p.id == reqPersonnelId) ? 'selected' : '';
                        } else {
                            // If reviewing an approved request, select the officially assigned person
                            if (status === 'Approved' && p.id == assignedPersonnelId) {
                                isSelected = 'selected';
                                labelSuffix += ' - ASSIGNED';
                            }
                            // If reviewing a rejected request, just select the requested person
                            else if (status === 'Rejected' && p.id == reqPersonnelId) {
                                isSelected = 'selected';
                            }
                        }

                        select.innerHTML += `<option value="${p.id}" ${isSelected}>${p.full_name} ${labelSuffix}</option>`;
                    });
                }
            })
            .catch(err => console.error("Error fetching personnel:", err));

        // Toggle Buttons for Process vs Review
        var actionBtns = document.getElementById('modal_action_buttons');
        var closeBtn = document.getElementById('modal_close_only_button');

        if (status === 'Pending') {
            actionBtns.classList.remove('hidden');
            actionBtns.classList.add('flex');
            closeBtn.classList.add('hidden');
        } else {
            actionBtns.classList.add('hidden');
            actionBtns.classList.remove('flex');
            closeBtn.classList.remove('hidden');
        }

        document.getElementById('processRequestModal').classList.remove('hidden');
        document.getElementById('processRequestModal').classList.add('flex');
    };

    window.closeReqModal = function() {
        document.getElementById('processRequestModal').classList.add('hidden');
        document.getElementById('processRequestModal').classList.remove('flex');
    };

    setTimeout(() => {
        // Listen to new Dept Filter
        var deptEl = document.getElementById('reqDeptFilter');
        if (deptEl) {
            deptEl.addEventListener('change', () => {
                if (typeof window.loadAdminRequests === 'function') window.loadAdminRequests(1);
            });
        }

        var searchEl = document.getElementById('reqSearchInput');
        if (searchEl) {
            searchEl.addEventListener('keyup', () => {
                clearTimeout(reqSearchTimer);
                reqSearchTimer = setTimeout(() => {
                    if (typeof window.loadAdminRequests === 'function') window.loadAdminRequests(1);
                }, 400);
            });
        }

        if (typeof window.loadAdminRequests === 'function') {
            window.loadAdminRequests(1);
        }
    }, 100);
</script>
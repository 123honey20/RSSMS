<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

require_once "../../backend/config/database.php";
// Fetch ALL columns so we have access to the req_ flags
$dept_query = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border min-h-[80vh] transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Direct Personnel Assignment</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a student below to officially assign their research personnel.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 items-center">

        <select id="assignServiceFilter" class="w-full border border-blue-200 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-300 rounded-lg px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors shadow-sm cursor-pointer">
            <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
            <option value="Librarian">Librarian</option>
            <option value="Human Grammarian">Human Grammarian</option>
            <option value="Ethics">Ethics</option>
        </select>

        <input type="text" id="assignSearchInput" placeholder="Search Student Control No. or Leader..."
            class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors shadow-sm">

        <select id="assignDeptFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Departments</option>
            <?php while ($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>" 
                    data-req-grammarly-ai="<?= isset($d['req_grammarly_ai']) ? $d['req_grammarly_ai'] : 1 ?>"
                    data-req-librarian="<?= isset($d['req_librarian']) ? $d['req_librarian'] : 1 ?>"
                    data-req-human-grammarian="<?= isset($d['req_human_grammarian']) ? $d['req_human_grammarian'] : 1 ?>"
                    data-req-ethics="<?= isset($d['req_ethics']) ? $d['req_ethics'] : 1 ?>"
                >
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select id="assignStatusFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors shadow-sm">
            <option value="All">All Statuses</option>
            <option value="Assigned">Assigned Only</option>
            <option value="Not Assigned">Not Assigned Only</option>
        </select>

    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold">Student Info</th>
                    <th class="px-6 py-4 font-semibold">Department</th>
                    <th class="px-6 py-4 font-semibold">Current Assignment</th>
                    <th class="px-6 py-4 font-semibold text-center">Student Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="assignTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="assignPaginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="assignRecordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="directAssignModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[9999] backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 border border-transparent dark:border-warmdark-border">

        <div class="bg-blue-900 dark:bg-warmdark-bg text-white px-6 py-4 flex items-center justify-between dark:border-b dark:border-warmdark-border">
            <h3 class="text-lg font-semibold tracking-wide flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Assign Personnel
            </h3>
            <button onclick="closeAssignModal()" class="text-white hover:text-gray-200 text-xl font-bold leading-none">✕</button>
        </div>

        <form action="../../backend/actions/admin_direct_assign_action.php" method="POST" class="p-6">
            <input type="hidden" name="student_id" id="modal_assign_student_id">
            <input type="hidden" name="service_type" id="modal_assign_service_type">

            <div class="mb-5 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border flex justify-between items-center">
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Student</p>
                    <p class="font-bold text-gray-900 dark:text-gray-100" id="modal_assign_student_name"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" id="modal_assign_control_no"></p>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Service Role</p>
                    <span class="inline-flex items-center bg-blue-100 dark:bg-blue-900/30 text-blue-900 dark:text-blue-400 px-2.5 py-1 rounded-md text-xs font-bold" id="modal_assign_service_display"></span>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-500 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Select Personnel</label>
                <select name="assigned_personnel_id" id="modal_assign_personnel_select" required class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors appearance-none">
                    <option value="">Loading available personnel...</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-2 italic">* This immediately approves the assignment and notifies the personnel.</p>
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 dark:border-warmdark-border pt-4">
                <button type="button" onclick="closeAssignModal()" class="w-1/3 bg-gray-100 dark:bg-warmdark-hover text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-warmdark-border border border-gray-200 dark:border-warmdark-border py-2.5 rounded-lg font-bold text-sm transition-colors">
                    Cancel
                </button>
                <button type="submit" class="w-2/3 bg-blue-900 text-white hover:bg-blue-800 py-2.5 rounded-lg font-bold text-sm shadow-md transition-colors">
                    Confirm Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var assignCurrentPage = 1;
    var assignSearchTimer;

    function updateDepartmentDropdown() {
        var service = document.getElementById('assignServiceFilter').value;
        var deptDropdown = document.getElementById('assignDeptFilter');
        var options = deptDropdown.querySelectorAll('option:not([value="All"])'); 
        
        var dataAttr = '';
        if (service === 'Grammarly & AI Checking') dataAttr = 'data-req-grammarly-ai';
        else if (service === 'Librarian') dataAttr = 'data-req-librarian';
        else if (service === 'Human Grammarian') dataAttr = 'data-req-human-grammarian';
        else if (service === 'Ethics') dataAttr = 'data-req-ethics';

        var currentSelectedIsDisabled = false;

        options.forEach(opt => {
            // Check if department requires this service (defaults to 1 if attribute is missing)
            var reqValue = opt.getAttribute(dataAttr);
            if (reqValue === '0') {
                opt.disabled = true;
                opt.style.display = 'none';
                if (opt.selected) currentSelectedIsDisabled = true;
            } else {
                opt.disabled = false;
                opt.style.display = '';
            }
        });

        if (currentSelectedIsDisabled) {
            deptDropdown.value = 'All';
        }
    }

    window.loadStudentsForAssignment = function(page = 1) {
        assignCurrentPage = page;
        var search = document.getElementById('assignSearchInput').value;
        var service = document.getElementById('assignServiceFilter').value;
        var dept = document.getElementById('assignDeptFilter').value;
        var statusFilter = document.getElementById('assignStatusFilter').value;

        fetch(`../../backend/ajax/fetch_students_for_assignment.php?p=${page}&search=${encodeURIComponent(search)}&service=${encodeURIComponent(service)}&dept=${encodeURIComponent(dept)}&assignment_status=${encodeURIComponent(statusFilter)}`)
            .then(res => res.json())
            .then(data => {
                var tbody = document.getElementById('assignTableBody');
                if (!tbody) return;

                tbody.innerHTML = '';

                if (!data.students || data.students.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No students found.</td></tr>`;
                    document.getElementById('assignPaginationContainer').innerHTML = '';
                    document.getElementById('assignRecordInfo').textContent = '';
                    return;
                }

                data.students.forEach(student => {
                    var row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    var assignmentHtml = '';
                    if (student.assigned_personnel_id) {
                        assignmentHtml = `<span class="inline-flex items-center gap-1.5 bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30 px-2.5 py-1 rounded-md text-[11px] font-bold">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            ${student.assigned_personnel_name}
                                          </span>`;
                    } else {
                        assignmentHtml = `<span class="inline-flex items-center gap-1.5 bg-gray-100 dark:bg-warmdark-bg text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-warmdark-border px-2.5 py-1 rounded-md text-[11px] font-bold">
                                            Not Assigned
                                          </span>`;
                    }

                    var actionBtnText = student.assigned_personnel_id ? 'Change' : 'Assign';
                    var actionBtnClass = student.assigned_personnel_id ?
                        'bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-warmdark-border hover:bg-gray-200 dark:hover:bg-warmdark-hover shadow-sm' :
                        'bg-blue-900 dark:bg-blue-800 text-white hover:bg-blue-800 dark:hover:bg-blue-700 shadow-sm border border-transparent';

                    row.innerHTML = `
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-200">${student.control_number}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${student.research_leader || 'No Leader Assigned'}</p>
                        </td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-400">${student.department_name || 'N/A'}</td>
                        <td class="px-6 py-4">${assignmentHtml}</td>
                        <td class="px-6 py-4 text-center">
                            <button onclick='openProfileStudent(${JSON.stringify(student).replace(/'/g, "&#39;").replace(/"/g, "&quot;")})' class="text-blue-600 dark:text-blue-400 hover:underline text-xs transition-colors">View Profile</button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="openAssignModal(${student.student_id}, '${student.research_leader || 'Student Group'}', '${student.control_number}', ${student.department_id}, '${service}', ${student.assigned_personnel_id || 'null'})" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-colors ${actionBtnClass}">
                                ${actionBtnText}
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                renderAssignPagination(data.total_pages, data.current_page);

                const totalRows = data.total_records || 0;
                const startRecord = (totalRows > 0) ? ((assignCurrentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(assignCurrentPage * 10, totalRows);

                document.getElementById('assignRecordInfo').textContent = totalRows > 0 ?
                    `Showing ${startRecord} - ${endRecord} of ${totalRows} Students` : '';
            })
            .catch(err => console.error("Error fetching students:", err));
    };

    function renderAssignPagination(totalPages, currentPage) {
        const container = document.getElementById('assignPaginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="loadStudentsForAssignment(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="loadStudentsForAssignment(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="loadStudentsForAssignment(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    window.openAssignModal = function(studentId, studentName, controlNo, deptId, serviceType, currentAssignedId) {
        document.getElementById('modal_assign_student_id').value = studentId;
        document.getElementById('modal_assign_service_type').value = serviceType;
        document.getElementById('modal_assign_student_name').textContent = studentName;
        document.getElementById('modal_assign_control_no').textContent = controlNo;
        document.getElementById('modal_assign_service_display').textContent = serviceType;

        var select = document.getElementById('modal_assign_personnel_select');
        select.innerHTML = '<option value="">Loading available personnel...</option>';

        fetch(`../../backend/ajax/get_personnel_for_assignment.php?dept=${deptId}&service=${encodeURIComponent(serviceType)}`)
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '<option value="" disabled selected>-- Select Personnel --</option>';
                if (!data || data.length === 0) {
                    select.innerHTML = '<option value="" disabled>No personnel available for this department</option>';
                } else {
                    data.forEach(p => {
                        let isSelected = (p.id == currentAssignedId) ? 'selected' : '';
                        let labelSuffix = (p.id == currentAssignedId) ? ' (Current)' : '';
                        select.innerHTML += `<option value="${p.id}" ${isSelected}>${p.full_name}${labelSuffix}</option>`;
                    });
                }
            });

        document.getElementById('directAssignModal').classList.remove('hidden');
        document.getElementById('directAssignModal').classList.add('flex');
    };

    window.closeAssignModal = function() {
        document.getElementById('directAssignModal').classList.add('hidden');
        document.getElementById('directAssignModal').classList.remove('flex');
    };

    // Event Listeners
    setTimeout(() => {
        document.getElementById('assignServiceFilter')?.addEventListener('change', () => {
            updateDepartmentDropdown();
            if (typeof window.loadStudentsForAssignment === 'function') window.loadStudentsForAssignment(1);
        });

        ['assignDeptFilter', 'assignStatusFilter'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                if (typeof window.loadStudentsForAssignment === 'function') window.loadStudentsForAssignment(1);
            });
        });

        var searchEl = document.getElementById('assignSearchInput');
        if (searchEl) {
            searchEl.addEventListener('keyup', () => {
                clearTimeout(assignSearchTimer);
                assignSearchTimer = setTimeout(() => {
                    if (typeof window.loadStudentsForAssignment === 'function') window.loadStudentsForAssignment(1);
                }, 400);
            });
        }

        // Initialize Everything on First Load
        updateDepartmentDropdown();
        if (typeof window.loadStudentsForAssignment === 'function') {
            window.loadStudentsForAssignment(1);
        }
    }, 100);
</script>
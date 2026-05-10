<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

require_once "../../backend/config/database.php";
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border min-h-[80vh] transition-colors duration-200" x-data="{ students: [] }">

    <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Batch Reassign Workload</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Transfer students from an inactive personnel to an active one within the same department.</p>
        </div>
    </div>

    <!-- CONTROL PANEL (4 STEPS) -->
    <div class="bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-xl p-6 mb-8 transition-colors shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Step 1: Service Selection -->
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">1. Select Service</label>
                <select id="reassignServiceFilter" class="w-full border border-blue-200 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-300 rounded-lg px-3 py-2.5 text-sm font-bold focus:ring-2 focus:ring-blue-900 focus:outline-none transition-colors shadow-sm cursor-pointer">
                    <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Human Grammarian">Human Grammarian</option>
                    <option value="Ethics">Ethics</option>
                    <option value="Statistician">Statistician</option>
                </select>
            </div>

            <!-- Step 2: Department Selection -->
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">2. Select Department</label>
                <select id="reassignDeptFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-warmdark-panel focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm text-gray-700 dark:text-gray-200 font-medium transition-colors cursor-pointer">
                    <option value="">-- Choose Department --</option>
                    <?php while ($d = $dept_query->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Step 3: FROM Personnel -->
            <div>
                <label class="block text-[10px] font-bold text-red-500 dark:text-red-400 uppercase tracking-widest mb-2">3. Transfer From (On Leave)</label>
                <select id="fromPersonnel" disabled class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2.5 text-sm bg-gray-100 dark:bg-warmdark-bg focus:ring-2 focus:ring-red-500 focus:outline-none shadow-sm text-gray-500 dark:text-gray-400 font-medium transition-colors cursor-not-allowed">
                    <option value="">Select a department first</option>
                </select>
            </div>

            <!-- Step 4: TO Personnel -->
            <div>
                <label class="block text-[10px] font-bold text-green-600 dark:text-green-500 uppercase tracking-widest mb-2">4. Transfer To (Active)</label>
                <select id="toPersonnel" disabled class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2.5 text-sm bg-gray-100 dark:bg-warmdark-bg focus:ring-2 focus:ring-green-500 focus:outline-none shadow-sm text-gray-500 dark:text-gray-400 font-medium transition-colors cursor-not-allowed">
                    <option value="">Select a department first</option>
                </select>
            </div>

        </div>
    </div>

    <!-- STUDENT SELECTION TABLE -->
    <form action="../../backend/actions/admin_reassign_workload_action.php" method="POST" onsubmit="return validateReassignment(event)">
        <input type="hidden" name="service_type" id="formServiceType" value="Grammarly & AI Checking">
        <input type="hidden" name="from_personnel_id" id="formFromPersonnel" value="">
        <input type="hidden" name="to_personnel_id" id="formToPersonnel" value="">

        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Currently Assigned Students
            </h3>
            <span id="selectedCountDisplay" class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[11px] font-bold px-3 py-1 rounded-full border border-blue-200 dark:border-blue-800/50">0 Selected</span>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
                <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                    <tr>
                        <th class="px-6 py-4 w-10 text-center">
                            <input type="checkbox" id="selectAllCheckbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600 cursor-pointer transition-all">
                        </th>
                        <th class="px-6 py-4 font-semibold">Student Control No.</th>
                        <th class="px-6 py-4 font-semibold">Research Leader</th>
                        <th class="px-6 py-4 font-semibold">Department / Course</th>
                        <th class="px-6 py-4 font-semibold text-center">Current Progress</th>
                        <th class="px-6 py-4 font-semibold text-center">Actions / History</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                <p class="font-medium">Please select a Department and a "Transfer From" personnel.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" id="submitReassignBtn" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-md transition-colors flex items-center gap-2 opacity-50 cursor-not-allowed" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                Confirm Batch Transfer
            </button>
        </div>
    </form>
</div>

<!-- HIDDEN FORM FOR SINGLE REVERT ACTION -->
<form id="revertForm" action="../../backend/actions/admin_reassign_workload_action.php" method="POST" class="hidden">
    <input type="hidden" name="service_type" id="revertServiceType">
    <input type="hidden" name="from_personnel_id" id="revertFromPersonnel">
    <input type="hidden" name="to_personnel_id" id="revertToPersonnel">
    <input type="hidden" name="student_ids[]" id="revertStudentId">
</form>

<!-- REVERT CONFIRMATION MODAL -->
<div id="revertConfirmModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[99999] backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[400px] max-w-[95%] rounded-2xl shadow-2xl p-6 border border-transparent dark:border-warmdark-border flex flex-col items-center text-center transform transition-all scale-95 opacity-0 duration-200" id="revertModalContentBox">
        
        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-4 shadow-sm border border-blue-200 dark:border-blue-800/50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
        </div>
        
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Confirm Revert</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" id="revertConfirmMessage">
            Are you sure you want to revert this student back to their original reviewer?
        </p>
        
        <div class="flex gap-3 w-full">
            <button type="button" onclick="cancelRevertAction()" class="flex-1 bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-warmdark-hover px-4 py-2.5 rounded-xl text-sm font-bold transition-colors border border-gray-200 dark:border-warmdark-border">
                Cancel
            </button>
            <button type="button" onclick="confirmRevertAction()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-md">
                Yes, Revert
            </button>
        </div>

    </div>
</div>

<!-- HISTORY MODAL -->
<div id="historyModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[99999] backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[500px] max-w-[95%] rounded-2xl shadow-2xl p-6 border border-transparent dark:border-warmdark-border flex flex-col max-h-[80vh]">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100 dark:border-warmdark-border">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Assignment History</h3>
            <button type="button" onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 font-bold text-xl leading-none">&times;</button>
        </div>
        
        <div id="historyModalContent" class="overflow-y-auto pr-2 custom-scrollbar flex-1 space-y-4">
            <!-- Timeline injected here -->
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-warmdark-border text-right">
            <button type="button" onclick="closeHistoryModal()" class="bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-warmdark-hover px-6 py-2.5 rounded-lg text-sm font-bold transition shadow-sm border border-gray-200 dark:border-warmdark-border">Close</button>
        </div>
    </div>
</div>

<!-- FULL SCREEN LOADING OVERLAY -->
<div id="reassignLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-amber-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Executing Transfer...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sending database updates and email notifications.</p>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const serviceSelect = document.getElementById('reassignServiceFilter');
        const deptSelect = document.getElementById('reassignDeptFilter');
        const fromSelect = document.getElementById('fromPersonnel');
        const toSelect = document.getElementById('toPersonnel');
        const tableBody = document.getElementById('studentTableBody');
        const selectAllCb = document.getElementById('selectAllCheckbox');
        const submitBtn = document.getElementById('submitReassignBtn');
        const selectedCountDisplay = document.getElementById('selectedCountDisplay');
        
        let allPersonnel = [];

        function resetPersonnelDropdowns() {
            fromSelect.innerHTML = '<option value="">Select a department first</option>';
            toSelect.innerHTML = '<option value="">Select a department first</option>';
            fromSelect.disabled = true;
            toSelect.disabled = true;
            fromSelect.classList.add('bg-gray-100', 'dark:bg-warmdark-bg', 'cursor-not-allowed');
            toSelect.classList.add('bg-gray-100', 'dark:bg-warmdark-bg', 'cursor-not-allowed');
            fromSelect.classList.remove('bg-white', 'dark:bg-warmdark-panel', 'cursor-pointer');
            toSelect.classList.remove('bg-white', 'dark:bg-warmdark-panel', 'cursor-pointer');
            resetTable();
        }

        function resetTable() {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">Please select a Department and a "Transfer From" personnel.</td></tr>`;
            selectAllCb.disabled = true;
            selectAllCb.checked = false;
            updateSubmitButton();
        }

        function fetchPersonnel() {
            const service = serviceSelect.value;
            const deptId = deptSelect.value;
            
            document.getElementById('formServiceType').value = service;
            
            if (!deptId) {
                resetPersonnelDropdowns();
                return;
            }

            fromSelect.disabled = false;
            toSelect.disabled = false;
            fromSelect.classList.remove('bg-gray-100', 'dark:bg-warmdark-bg', 'cursor-not-allowed');
            toSelect.classList.remove('bg-gray-100', 'dark:bg-warmdark-bg', 'cursor-not-allowed');
            fromSelect.classList.add('bg-white', 'dark:bg-warmdark-panel', 'cursor-pointer');
            toSelect.classList.add('bg-white', 'dark:bg-warmdark-panel', 'cursor-pointer');

            fromSelect.innerHTML = '<option value="">Loading...</option>';
            toSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`../../backend/ajax/get_personnel_by_service.php?service=${encodeURIComponent(service)}&dept_id=${deptId}`)
                .then(res => res.json())
                .then(data => {
                    allPersonnel = data;
                    populateDropdowns();
                });
        }

        function populateDropdowns() {
            let optionsHTML = '<option value="">-- Select Personnel --</option>';
            if (allPersonnel.length === 0) {
                optionsHTML = '<option value="">No personnel mapped to this department.</option>';
            } else {
                allPersonnel.forEach(p => {
                    optionsHTML += `<option value="${p.id}">${p.full_name} (${p.workload} active students)</option>`;
                });
            }
            fromSelect.innerHTML = optionsHTML;
            toSelect.innerHTML = optionsHTML;
            
            resetTable();
        }

        function fetchStudents() {
            const fromId = fromSelect.value;
            const service = serviceSelect.value;
            document.getElementById('formFromPersonnel').value = fromId;
            
            Array.from(toSelect.options).forEach(opt => {
                opt.disabled = (opt.value === fromId && fromId !== '');
            });

            if (!fromId) {
                resetTable();
                return;
            }

            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">Loading students...</td></tr>`;

            fetch(`../../backend/ajax/get_students_by_personnel.php?service=${encodeURIComponent(service)}&personnel_id=${fromId}`)
                .then(res => res.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    if (data.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">This personnel has no active students in this service.</td></tr>`;
                        selectAllCb.disabled = true;
                    } else {
                        data.forEach(student => {
                            let badgeClass = student.current_progress === 'Completed'
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50'
                                : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-800/50';

                            // Render Checkbox OR Locked Icon based on 'is_completed' flag
                            let checkboxHtml = '';
                            if (student.is_completed) {
                                checkboxHtml = `<div class="flex justify-center" title="Cannot reassign completed students">
                                                    <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </div>`;
                            } else {
                                checkboxHtml = `<input type="checkbox" name="student_ids[]" value="${student.student_id}" class="student-cb w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer transition-all">`;
                            }

                            // Render "Revert" button if they have an original personnel DIFFERENT from the current one
                            let revertBtnHtml = '';
                            if (student.original_personnel_id && student.original_personnel_id != fromId && !student.is_completed) {
                                // Escape name properly for the onclick function
                                const safeName = student.original_personnel_name.replace(/'/g, "\\'");
                                revertBtnHtml = `
                                    <button type="button" onclick="revertToOriginal(${student.student_id}, ${student.original_personnel_id}, '${safeName}')" class="bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm border border-blue-200 dark:border-blue-800/50 mt-1 w-full flex items-center justify-center gap-1" title="Revert to ${student.original_personnel_name}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                        Revert
                                    </button>
                                `;
                            }

                            tableBody.innerHTML += `
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors group ${student.is_completed ? 'bg-gray-50/30 dark:bg-warmdark-bg/30' : ''}">
                                    <td class="px-6 py-4 text-center align-middle">
                                        ${checkboxHtml}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-200">${student.control_number}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">${student.research_leader}</td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="block text-gray-800 dark:text-gray-200 font-semibold mb-0.5">${student.course_name}</span>
                                        ${student.dept_name}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-md text-[11px] font-bold border inline-block whitespace-nowrap ${badgeClass}">
                                            ${student.current_progress}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center align-middle">
                                        <button type="button" onclick="viewHistory(${student.student_id})" class="bg-gray-100 hover:bg-gray-200 dark:bg-warmdark-bg dark:hover:bg-warmdark-border text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm border border-gray-200 dark:border-warmdark-border w-full">
                                            Log
                                        </button>
                                        ${revertBtnHtml}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    attachCheckboxListeners();
                    updateSubmitButton();
                });
        }

        function attachCheckboxListeners() {
            const checkboxes = document.querySelectorAll('.student-cb');
            
            // Disable selectAll if there are NO active checkboxes (i.e. all students are completed)
            if (checkboxes.length === 0) {
                selectAllCb.disabled = true;
                selectAllCb.checked = false;
            } else {
                selectAllCb.disabled = false;
                selectAllCb.checked = false; 
            }
            
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    selectAllCb.checked = checkboxes.length > 0 && allChecked;
                    updateSubmitButton();
                });
            });

            selectAllCb.addEventListener('change', (e) => {
                checkboxes.forEach(cb => cb.checked = e.target.checked);
                updateSubmitButton();
            });
        }

        function updateSubmitButton() {
            const checkboxes = document.querySelectorAll('.student-cb:checked');
            const toId = toSelect.value;
            document.getElementById('formToPersonnel').value = toId;
            
            selectedCountDisplay.textContent = `${checkboxes.length} Selected`;

            if (checkboxes.length > 0 && toId !== '') {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        serviceSelect.addEventListener('change', fetchPersonnel);
        deptSelect.addEventListener('change', fetchPersonnel);
        fromSelect.addEventListener('change', fetchStudents);
        toSelect.addEventListener('change', updateSubmitButton);
        
        // Single Revert Modal Logic
        window.revertToOriginal = function(studentId, originalId, originalName) {
            // 1. Set values in the hidden form
            document.getElementById('revertServiceType').value = document.getElementById('reassignServiceFilter').value;
            document.getElementById('revertFromPersonnel').value = document.getElementById('fromPersonnel').value;
            document.getElementById('revertToPersonnel').value = originalId;
            document.getElementById('revertStudentId').value = studentId;

            // 2. Update modal message text
            document.getElementById('revertConfirmMessage').innerHTML = `Are you sure you want to revert this student back to their original reviewer (<strong>${originalName}</strong>)?`;

            // 3. Show the modal with transition
            const modal = document.getElementById('revertConfirmModal');
            const box = document.getElementById('revertModalContentBox');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Small timeout allows the display to render before animating opacity/scale
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        window.cancelRevertAction = function() {
            const modal = document.getElementById('revertConfirmModal');
            const box = document.getElementById('revertModalContentBox');
            
            box.classList.remove('scale-100', 'opacity-100');
            box.classList.add('scale-95', 'opacity-0');
            
            // Wait for transition to finish before hiding the container
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }

        window.confirmRevertAction = function() {
            // Hide the confirmation modal
            cancelRevertAction();
            
            // Show the loading overlay so the user knows it's working
            document.getElementById('reassignLoadingOverlay').classList.remove('hidden');
            document.getElementById('reassignLoadingOverlay').classList.add('flex');

            // Submit the form
            document.getElementById('revertForm').submit();
        }

        window.viewHistory = function(studentId) {
            const service = document.getElementById('reassignServiceFilter').value;
            const container = document.getElementById('historyModalContent');
            
            container.innerHTML = `<div class="text-center py-8 text-gray-500">Loading history...</div>`;
            document.getElementById('historyModal').classList.remove('hidden');
            document.getElementById('historyModal').classList.add('flex');

            fetch(`../../backend/ajax/fetch_reassignment_history.php?student_id=${studentId}&service=${encodeURIComponent(service)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        container.innerHTML = `<div class="text-red-500 text-center">${data.error}</div>`;
                        return;
                    }

                    let html = `
                        <div class="flex items-start gap-4 mb-5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold shrink-0 mt-1 shadow-sm">1</div>
                            <div class="bg-gray-50 dark:bg-warmdark-bg p-3 rounded-lg border border-gray-200 dark:border-warmdark-border w-full shadow-sm">
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Original Assignment (Application)</p>
                                <p class="font-bold text-gray-800 dark:text-gray-200">${data.original}</p>
                                <p class="text-xs text-gray-400 mt-1">${data.applied_date}</p>
                            </div>
                        </div>
                    `;

                    if (data.logs.length === 0) {
                        html += `<div class="text-center text-sm text-gray-400 italic py-4">No reassignments recorded yet.</div>`;
                    } else {
                        data.logs.forEach((log, index) => {
                            let isRevert = (log.to_personnel === data.original);
                            
                            let circleColor = isRevert ? 'bg-emerald-100 text-emerald-600 border-emerald-200' : 'bg-amber-100 text-amber-600 border-amber-200';
                            let boxColor = isRevert ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200';
                            let textColor = isRevert ? 'text-emerald-600' : 'text-amber-600';
                            let label = isRevert ? 'Reverted to Original' : 'Reassigned To';
                            let icon = isRevert ? `<svg class="w-3 h-3 inline mb-0.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>` : '';

                            html += `
                                <div class="flex items-start gap-4 mb-5 relative">
                                    <div class="absolute left-4 -top-6 bottom-0 w-[2px] bg-gray-200 dark:bg-warmdark-border -z-10 h-10"></div>
                                    <div class="w-8 h-8 rounded-full ${circleColor} flex items-center justify-center font-bold shrink-0 mt-1 shadow-sm border">${index + 2}</div>
                                    <div class="${boxColor} dark:bg-warmdark-bg p-3 rounded-lg border dark:border-warmdark-border w-full shadow-sm">
                                        <p class="text-[10px] ${textColor} font-bold uppercase tracking-wider mb-0.5">${label}${icon}</p>
                                        <p class="font-bold text-gray-800 dark:text-gray-200">${log.to_personnel}</p>
                                        <p class="text-xs text-gray-400 mt-1 flex justify-between">
                                            <span>Transferred from: ${log.from_personnel}</span>
                                            <span>${log.formatted_date}</span>
                                        </p>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    container.innerHTML = html;
                });
        };

        window.closeHistoryModal = function() {
            document.getElementById('historyModal').classList.add('hidden');
            document.getElementById('historyModal').classList.remove('flex');
        };
    });

    function validateReassignment(event) {
        const toId = document.getElementById('formToPersonnel').value;
        const checkboxes = document.querySelectorAll('.student-cb:checked');

        if (!toId || checkboxes.length === 0) {
            event.preventDefault();
            alert("Please select a target personnel and at least one student.");
            return false;
        }

        document.getElementById('reassignLoadingOverlay').classList.remove('hidden');
        document.getElementById('reassignLoadingOverlay').classList.add('flex');
        
        const btn = document.getElementById('submitReassignBtn');
        btn.disabled = true;
        btn.innerHTML = 'Executing...';
        return true;
    }
</script>
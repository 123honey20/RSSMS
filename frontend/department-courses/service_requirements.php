<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized access!";
    exit();
}

require_once "../../backend/config/database.php";

// Fetch all departments for the dropdown
$deptQuery = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = [];
while ($row = $deptQuery->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch all courses grouped by department for JS dynamic loading
$courseQuery = $conn->query("SELECT id, department_id, name FROM courses ORDER BY name ASC");
$coursesByDept = [];
while ($row = $courseQuery->fetch_assoc()) {
    $coursesByDept[$row['department_id']][] = $row;
}

// Fetch all existing course requirements to display in the table
$rulesQuery = $conn->query("
    SELECT r.*, c.name AS course_name, d.name AS dept_name 
    FROM course_service_requirements r
    JOIN courses c ON r.course_id = c.id
    JOIN departments d ON c.department_id = d.id
    ORDER BY d.name ASC, c.name ASC, r.service_type ASC
");
$rules = [];
if ($rulesQuery) { 
    while ($row = $rulesQuery->fetch_assoc()) {
        $rules[] = $row;
    }
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 w-full transition-colors duration-200">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="fixed top-6 right-6 bg-emerald-600 text-white px-5 py-4 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0 font-medium">
            <div class="bg-white/20 rounded-full p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
            </div>
            <span class="text-sm"><?php echo $_SESSION['flash_success']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-success')?.classList.add('opacity-0', 'translate-x-full'); }, 3000);</script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="toast-error" class="fixed top-6 right-6 bg-red-600 text-white px-5 py-4 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0 font-medium">
            <div class="bg-white/20 rounded-full p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </div>
            <span class="text-sm"><?php echo $_SESSION['flash_error']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-error')?.classList.add('opacity-0', 'translate-x-full'); }, 4000);</script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="bg-blue-100 dark:bg-blue-900/40 p-2 rounded-lg text-blue-600 dark:text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Course Requirements Config</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 ml-12">Establish dynamic phase rules and strict round limits specific to each course.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-base font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Add / Update Rule
                    </h2>
                </div>
                
                <div class="p-6">
                    <form action="../../backend/actions/department-courses/save_service_requirements.php" method="POST" onsubmit="return validateForm()">
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Target Department</label>
                                <div class="relative">
                                    <select id="deptSelect" required class="block w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all appearance-none shadow-sm cursor-pointer font-medium" onchange="renderCourses(this.value)">
                                        <option value="" disabled selected>-- Select Department --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>

                            <div id="courseSelectionContainer" class="hidden bg-gray-50/50 dark:bg-warmdark-bg/50 border border-gray-200 dark:border-warmdark-border rounded-xl p-4 transition-all">
                                <div class="flex justify-between items-center mb-3 pb-2 border-b border-gray-200 dark:border-warmdark-border">
                                    <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Select Courses</label>
                                    <div class="flex gap-3 text-[11px] font-bold">
                                        <button type="button" onclick="toggleAllCourses(true)" class="text-blue-600 dark:text-blue-400 hover:underline">Check All</button>
                                        <button type="button" onclick="toggleAllCourses(false)" class="text-gray-500 dark:text-gray-400 hover:underline">Uncheck All</button>
                                    </div>
                                </div>
                                <div id="coursesList" class="max-h-40 overflow-y-auto custom-scrollbar space-y-2 pr-2">
                                    </div>
                                <p id="courseError" class="hidden text-red-500 text-xs font-semibold mt-2">Please select at least one course.</p>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Service Type</label>
                                <div class="relative">
                                    <select name="service_type" required class="block w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all appearance-none shadow-sm cursor-pointer font-medium">
                                        <option value="" disabled selected>-- Select Service --</option>
                                        <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                                        <option value="Human Grammarian">Human Grammarian</option>
                                        <option value="Ethics">Ethics</option>
                                        <option value="Statistician">Statistician</option>
                                        <option value="Librarian">Librarian</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-5 pt-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2" title="How many times must they be approved?">Total Phases</label>
                                    <input type="number" name="required_phases" value="1" min="1" max="5" required class="block w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all shadow-sm font-semibold text-center">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2" title="Max rounds allowed per phase">Round Limit</label>
                                    <input type="number" name="round_limit_per_phase" value="7" min="1" max="20" required class="block w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all shadow-sm font-semibold text-center">
                                </div>
                            </div>

                            <div class="pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border">
                                <button type="submit" id="saveRuleBtn" class="w-full bg-blue-600 dark:bg-blue-700 text-white px-4 py-3 rounded-xl text-sm font-bold shadow-md shadow-blue-500/30 hover:bg-blue-700 dark:hover:bg-blue-600 hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                    Save Configuration
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors flex flex-col h-full">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-warmdark-border flex items-center justify-between bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-base font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                        Active Course Configurations
                    </h2>
                    <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 text-xs font-bold px-3 py-1 rounded-full border border-blue-200 dark:border-blue-800/50 shadow-sm">
                        <?= count($rules) ?> Rule<?= count($rules) !== 1 ? 's' : '' ?> Set
                    </span>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-warmdark-bg/80 border-b border-gray-200 dark:border-warmdark-border text-[11px] uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-4 font-bold">Course / Department</th>
                                <th class="px-6 py-4 font-bold">Service Target</th>
                                <th class="px-6 py-4 font-bold text-center">Req. Phases</th>
                                <th class="px-6 py-4 font-bold text-center">Max Rounds</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-warmdark-border">
                            <?php if (empty($rules)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            <p class="font-medium text-gray-500 dark:text-gray-400 text-base">No Custom Configurations Found</p>
                                            <p class="text-xs mt-1">The system defaults (1 Phase, 7 Rounds) are currently active for all courses.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rules as $rule): ?>
                                    <tr class="hover:bg-blue-50/50 dark:hover:bg-warmdark-hover transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800 dark:text-gray-100">
                                                <?= htmlspecialchars($rule['course_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <?= htmlspecialchars($rule['dept_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50 shadow-sm">
                                                <?= htmlspecialchars($rule['service_type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-bold text-gray-700 dark:text-gray-300 text-sm bg-gray-100 dark:bg-warmdark-bg px-3 py-1 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-inner">
                                                <?= $rule['required_phases'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-bold text-gray-700 dark:text-gray-300 text-sm bg-gray-100 dark:bg-warmdark-bg px-3 py-1 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-inner">
                                                <?= $rule['round_limit_per_phase'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="configLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-3xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-12 w-12 text-blue-600 dark:text-blue-400 mb-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">Saving Configuration...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Applying new rules to the selected courses.</p>
    </div>
</div>

<script>
    // JS Object populated directly from PHP Database
    const coursesData = <?= json_encode($coursesByDept) ?>;

    function renderCourses(deptId) {
        const container = document.getElementById('courseSelectionContainer');
        const list = document.getElementById('coursesList');
        const errorMsg = document.getElementById('courseError');
        
        list.innerHTML = '';
        errorMsg.classList.add('hidden');

        if (!deptId || !coursesData[deptId]) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        
        // Render checkboxes with modern styling
        coursesData[deptId].forEach(course => {
            const html = `
                <label class="flex items-center p-3 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 transition-colors group shadow-sm">
                    <input type="checkbox" name="course_ids[]" value="${course.id}" class="course-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                    <span class="ml-3 text-sm font-semibold text-gray-700 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">${course.name}</span>
                </label>
            `;
            list.insertAdjacentHTML('beforeend', html);
        });
    }

    function toggleAllCourses(check) {
        const checkboxes = document.querySelectorAll('.course-checkbox');
        checkboxes.forEach(cb => cb.checked = check);
    }

    function validateForm() {
        const checkboxes = document.querySelectorAll('.course-checkbox:checked');
        if (checkboxes.length === 0) {
            document.getElementById('courseError').classList.remove('hidden');
            return false;
        }
        
        document.getElementById('configLoadingOverlay').classList.remove('hidden');
        document.getElementById('configLoadingOverlay').classList.add('flex');
        const btn = document.getElementById('saveRuleBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
        }
        return true;
    }
</script>
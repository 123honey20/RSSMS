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

// Fetch all existing service requirements to display in the table
$rulesQuery = $conn->query("
    SELECT r.*, d.name AS dept_name 
    FROM department_service_requirements r
    JOIN departments d ON r.department_id = d.id
    ORDER BY d.name ASC, r.service_type ASC
");
$rules = [];
while ($row = $rulesQuery->fetch_assoc()) {
    $rules[] = $row;
}
?>

<div class="max-w-6xl mx-auto py-8 px-4 w-full transition-colors duration-200">

    <!-- Toast Notifications -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="fixed top-6 right-6 bg-green-600 dark:bg-green-700 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
            <span class="font-medium text-sm"><?php echo $_SESSION['flash_success']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-success')?.classList.add('opacity-0', 'translate-x-full'); }, 3000);</script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="toast-error" class="fixed top-6 right-6 bg-red-600 dark:bg-red-700 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            <span class="font-medium text-sm"><?php echo $_SESSION['flash_error']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-error')?.classList.add('opacity-0', 'translate-x-full'); }, 4000);</script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Service Requirements</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure phase requirements and round limits for specific departments and services.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- FORM SECTION -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-6 transition-colors">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4 border-b border-gray-100 dark:border-warmdark-border pb-3">Add / Update Rule</h2>
                
                <form action="../../backend/actions/department-courses/save_service_requirement.php" method="POST" onsubmit="showConfigLoader()">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Department</label>
                            <select name="department_id" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Service Type</label>
                            <select name="service_type" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors">
                                <option value="">-- Select Service --</option>
                                <option value="Human Grammarian">Human Grammarian</option>
                                <option value="Ethics">Ethics</option>
                                <option value="Statistician">Statistician</option>
                                <option value="Librarian">Librarian</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2" title="How many times must they be approved?">Required Phases</label>
                                <input type="number" name="required_phases" value="1" min="1" max="5" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2" title="Max rounds allowed per phase">Round Limit</label>
                                <input type="number" name="round_limit_per_phase" value="7" min="1" max="20" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-warmdark-border">
                            <button type="submit" id="saveRuleBtn" class="w-full bg-blue-600 dark:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                Save Configuration
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-6 overflow-hidden transition-colors">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">Active Configurations</h2>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-warmdark-bg border-b border-gray-200 dark:border-warmdark-border text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="p-3 font-bold">Department</th>
                                <th class="p-3 font-bold">Service</th>
                                <th class="p-3 font-bold text-center">Phases</th>
                                <th class="p-3 font-bold text-center">Round Limit</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-warmdark-border">
                            <?php if (empty($rules)): ?>
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400 italic">No configurations set. Defaults (1 Phase, 7 Rounds) will apply.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rules as $rule): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-warmdark-hover transition-colors">
                                        <td class="p-3 font-medium text-gray-800 dark:text-gray-200"><?= htmlspecialchars($rule['dept_name']) ?></td>
                                        <td class="p-3 text-blue-600 dark:text-blue-400 font-semibold"><?= htmlspecialchars($rule['service_type']) ?></td>
                                        <td class="p-3 text-center text-gray-700 dark:text-gray-300 font-bold"><?= $rule['required_phases'] ?></td>
                                        <td class="p-3 text-center text-gray-700 dark:text-gray-300 font-bold"><?= $rule['round_limit_per_phase'] ?></td>
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

<!-- LOCAL LOADING OVERLAY -->
<div id="configLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Saving Configuration...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please wait while the system updates the rules.</p>
    </div>
</div>

<script>
    function showConfigLoader() {
        document.getElementById('configLoadingOverlay').classList.remove('hidden');
        document.getElementById('configLoadingOverlay').classList.add('flex');
        const btn = document.getElementById('saveRuleBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Saving...';
        }
    }
</script>
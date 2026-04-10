<?php
require_once "../../backend/config/database.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid department.");
}

// CHANGED: Select all columns to get the service requirements
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Department not found.");
}

$department = $result->fetch_assoc();

$error_msg = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'duplicate_dept') {
        $error_msg = "Another department is already using this name.";
    } elseif ($_GET['error'] === 'failed') {
        $error_msg = "Failed to update department. Please try again.";
    }
}
?>

<div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm max-w-2xl mx-auto border border-gray-100 dark:border-warmdark-border transition-colors duration-200">
    <div class="mb-8 border-b border-gray-200 dark:border-warmdark-border pb-4 transition-colors">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Edit Department</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update the department name and required research services.</p>
    </div>

    <?php if ($error_msg): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 mb-6 rounded-lg border border-red-100 dark:border-red-900/30 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="../../backend/controllers/department-course-controller/edit_department_course_controller.php" class="space-y-6">
        <input type="hidden" name="department_id" value="<?= $id; ?>">
        <input type="hidden" name="action" value="update_department">

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Department Name</label>
            <input type="text" name="department_name" 
                value="<?= htmlspecialchars($department['name']); ?>" required 
                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
        </div>

        <div class="pt-4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Required Research Services</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                
                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-warmdark-hover transition">
                    <input type="checkbox" name="req_grammarly_ai" value="1" <?= (!isset($department['req_grammarly_ai']) || $department['req_grammarly_ai']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Grammarly & AI Checking</span>
                </label>

                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-warmdark-hover transition">
                    <input type="checkbox" name="req_ethics" value="1" <?= (!isset($department['req_ethics']) || $department['req_ethics']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Ethics</span>
                </label>

                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-warmdark-hover transition">
                    <input type="checkbox" name="req_human_grammarian" value="1" <?= (!isset($department['req_human_grammarian']) || $department['req_human_grammarian']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Human Grammarian</span>
                </label>

                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-warmdark-hover transition">
                    <input type="checkbox" name="req_librarian" value="1" <?= (!isset($department['req_librarian']) || $department['req_librarian']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Librarian</span>
                </label>

                <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-warmdark-border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-warmdark-hover transition">
                    <input type="checkbox" name="req_statistician" value="1" <?= (!isset($department['req_statistician']) || $department['req_statistician']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Statistician</span>
                </label>

            </div>
            <p class="text-[11px] text-gray-400 mt-2 italic">* Uncheck a service if this department does not require it for their thesis/capstone.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <a href="../dashboards/admin_dashboard.php?page=view_departments" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg hover:bg-gray-200 dark:hover:bg-warmdark-border transition-all border border-transparent dark:border-warmdark-border">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 dark:bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 dark:hover:bg-blue-700 hover:shadow-lg transition-all">
                Update Department
            </button>
        </div>
    </form>
</div>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$query = "
    SELECT 
        r.id, 
        r.name, 
        r.created_at,
        (SELECT COUNT(*) FROM rubric_criteria rc WHERE rc.rubric_id = r.id) as criteria_count
    FROM rubrics r
    ORDER BY r.created_at DESC
";
$result = $conn->query($query);
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm min-h-[80vh] border border-transparent dark:border-warmdark-border transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Rubrics Management</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage evaluation criteria for the student feedback system.</p>
        </div>
        <a href="admin_dashboard.php?page=feedback_admin_add" class="bg-blue-900 dark:bg-blue-800 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Create New Rubric
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold">Rubric Name</th>
                    <th class="px-6 py-4 font-semibold text-center">Total Criteria</th>
                    <th class="px-6 py-4 font-semibold text-center">Date Created</th>
                    <th class="px-6 py-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-bold px-2.5 py-1 rounded-md text-xs border border-transparent dark:border-blue-900/50">
                                    <?php echo $row['criteria_count']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <a href="admin_dashboard.php?page=feedback_admin_edit&id=<?php echo $row['id']; ?>" class="text-blue-700 dark:text-blue-400 hover:underline text-xs">Edit</a>
                                <button onclick="deleteRubric(<?php echo $row['id']; ?>)" class="text-red-600 dark:text-red-400 hover:underline text-xs">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="font-medium text-gray-500 dark:text-gray-400">No rubrics found</p>
                            <p class="text-xs mt-1 dark:text-gray-500">Click "Create New Rubric" to get started.</p>
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<script>
function deleteRubric(id) {
    if (confirm("Are you sure you want to delete this rubric? This will also permanently delete all its criteria and scoring levels.")) {
        alert("Delete functionality coming up next for Rubric ID: " + id);
    }
}
</script>
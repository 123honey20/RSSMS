<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

// Fetch all rubrics and dynamically count how many criteria each one has
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

<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Rubrics Management</h2>
            <p class="text-sm text-gray-500">Manage evaluation criteria for the student feedback system.</p>
        </div>
        <a href="admin_dashboard.php?page=feedback_admin_add" class="bg-blue-900 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-blue-800 transition shadow-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Create New Rubric
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold">Rubric Name</th>
                    <th class="px-6 py-4 font-semibold text-center">Total Criteria</th>
                    <th class="px-6 py-4 font-semibold text-center">Date Created</th>
                    <th class="px-6 py-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-md text-xs">
                                    <?php echo $row['criteria_count']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <a href="admin_dashboard.php?page=feedback_admin_edit&id=<?php echo $row['id']; ?>" class="text-blue-700 hover:underline text-xs">Edit</a>
                                <button onclick="deleteRubric(<?php echo $row['id']; ?>)" class="text-red-600 hover:underline text-xs">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="font-medium text-gray-500">No rubrics found</p>
                            <p class="text-xs mt-1">Click "Create New Rubric" to get started.</p>
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
        // We will build the backend delete route next!
        alert("Delete functionality coming up next for Rubric ID: " + id);
    }
}
</script>
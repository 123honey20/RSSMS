<?php
require_once "../../backend/config/database.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid department.");
}

$stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Department not found.");
}

$department = $result->fetch_assoc();
?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Edit Department</h1>

    <form method="POST"
          action="../../backend/controllers/department-course-controller/edit_department_course_controller.php"
          class="space-y-3">

        <input type="text"
               name="department_name"
               value="<?= htmlspecialchars($department['name']); ?>"
               required
               class="w-full border p-2 rounded">

        <input type="hidden" name="department_id" value="<?= $id; ?>">
        <input type="hidden" name="action" value="update_department">

        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=view_departments"
               class="text-gray-600 hover:underline">Cancel</a>

            <button type="submit"
                    class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">
                Update Department
            </button>
        </div>
    </form>
</div>

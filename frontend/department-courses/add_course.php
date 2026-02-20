<?php
require_once "../../backend/config/database.php";
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Add Course</h1>

    <form method="POST" action="../../backend/controllers/department-course-controller/add_department_course_controller.php" class="space-y-3">

        <input type="text"
            name="course_name"
            placeholder="Course Name"
            required
            class="w-full border p-2 rounded">

        <select name="department_id"
            required
            class="w-full border p-2 rounded">
            <option value="">Select Department</option>

            <?php while ($row = $departments->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>">
                    <?= htmlspecialchars($row['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="hidden" name="action" value="add_course">

        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=view_courses"
                class="text-gray-600 hover:underline">Cancel</a>

            <button type="submit"
                class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">
                Save Course
            </button>
        </div>
    </form>

</div>
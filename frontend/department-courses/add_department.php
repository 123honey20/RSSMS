<?php
require_once "../../backend/config/database.php";

?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Add Department</h1>

    <form method="POST" action="../../backend/controllers/department-course-controller/add_department_course_controller.php" class="space-y-3">

        <input type="text" name="department_name"
            placeholder="Department Name"
            required
            class="w-full border p-2 rounded">

        <input type="hidden" name="action" value="add_department">

        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=view_departments"
                class="text-gray-600 hover:underline">Cancel</a>

            <button type="submit"
                class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">
                Save Department
            </button>
        </div>
    </form>

</div>
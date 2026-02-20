<?php
require_once "../../backend/config/database.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid course.");
}

// Get course
$stmt = $conn->prepare("SELECT name, department_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Course not found.");
}

$course = $result->fetch_assoc();

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Edit Course</h1>

    <form method="POST"
          action="../../backend/controllers/department-course-controller/edit_department_course_controller.php"
          class="space-y-3">

        <input type="text"
               name="course_name"
               value="<?= htmlspecialchars($course['name']); ?>"
               required
               class="w-full border p-2 rounded">

        <select name="department_id"
                required
                class="w-full border p-2 rounded">

            <?php while ($row = $departments->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>"
                    <?= $row['id'] == $course['department_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($row['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="hidden" name="course_id" value="<?= $id; ?>">
        <input type="hidden" name="action" value="update_course">

        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=view_courses"
               class="text-gray-600 hover:underline">Cancel</a>

            <button type="submit"
                    class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">
                Update Course
            </button>
        </div>
    </form>
</div>

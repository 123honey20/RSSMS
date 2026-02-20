<?php
// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Student ID");
}
// Fetch all departments
$departmentsQuery = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

$user_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id'];
    $email = $_POST['email'];
    $thesis_title = $_POST['thesis_title'];
    $control_number = $_POST['control_number'];
    $research_leader = $_POST['research_leader'];
    $department_id = $_POST['department_id'];
    $course_id     = $_POST['course_id'];

    // Update users table
    $stmt1 = $conn->prepare("UPDATE users SET school_id = ?, email = ? WHERE id = ?");
    $stmt1->bind_param("ssi", $school_id, $email, $user_id);
    $stmt1->execute();

    // Check if student row exists
    $check = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Update students table
        $stmt2 = $conn->prepare("
        UPDATE students SET thesis_title = ?, control_number = ?, research_leader = ?, department_id = ?, course_id = ?
        WHERE user_id = ?
        ");
        $stmt2->bind_param(
            "sssiii",
            $thesis_title,
            $control_number,
            $research_leader,
            $department_id,
            $course_id,
            $user_id
        );
        $stmt2->execute();
    } else {
        // Insert if not exists
        $stmt2 = $conn->prepare("
            INSERT INTO students (user_id, thesis_title, control_number, research_leader, department_id, course_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param(
            "isssii",
            $user_id,
            $thesis_title,
            $control_number,
            $research_leader,
            $department_id,
            $course_id
        );
        $stmt2->execute();
    }

    echo "<script>
    window.location.href = '../dashboards/admin_dashboard.php?page=students&updated=1';
    </script>";
    exit();
}

// Fetch student data
$stmt = $conn->prepare("
    SELECT u.school_id, u.email,
           s.thesis_title, s.control_number, s.research_leader,
           s.department_id, s.course_id
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    WHERE u.id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Student not found");
}
?>
<div class="bg-white p-6 rounded-xl shadow max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-6">Edit Student Profile</h2>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium">School ID</label>
            <input type="text" name="school_id" required
                value="<?php echo htmlspecialchars($data['school_id']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" required
                value="<?php echo htmlspecialchars($data['email']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium">Thesis Title</label>
            <input type="text" name="thesis_title"
                value="<?php echo htmlspecialchars($data['thesis_title']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium">Control Number</label>
            <input type="text" name="control_number"
                value="<?php echo htmlspecialchars($data['control_number']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium">Research Leader</label>
            <input type="text" name="research_leader"
                value="<?php echo htmlspecialchars($data['research_leader']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <label class="block text-sm font-medium">Department</label>
        <select name="department_id" id="studentDepartment" required
            class="w-full border rounded px-3 py-2">
            <option value="">Select Department</option>
            <?php while ($row = $departmentsQuery->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>"
                    <?= ($data['department_id'] == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label class="block text-sm font-medium">Course</label>
        <select name="course_id" id="studentCourse" required
            class="w-full border rounded px-3 py-2">
            <option value="">Select Course</option>
        </select>



        <div class="flex justify-end gap-2 pt-4">
            <a href="admin_dashboard.php?page=students"
                class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                Cancel
            </a>
            <button type="submit"
                class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">
                Update Student
            </button>
        </div>
    </form>
</div>

<script>
    const departmentSelect = document.getElementById("studentDepartment");
    const courseSelect = document.getElementById("studentCourse");

    // Populate courses when page loads
    function loadCourses(departmentId, selectedCourseId = null) {
        courseSelect.innerHTML = '<option value="">Loading...</option>';
        if (!departmentId) {
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            return;
        }

        fetch("../../backend/ajax/get_courses.php?department_id=" + departmentId)
            .then(res => res.json())
            .then(data => {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                data.forEach(course => {
                    const option = document.createElement("option");
                    option.value = course.id;
                    option.textContent = course.name;
                    if (selectedCourseId && selectedCourseId == course.id) {
                        option.selected = true;
                    }
                    courseSelect.appendChild(option);
                });
            })
            .catch(err => {
                courseSelect.innerHTML = '<option value="">Error loading courses</option>';
            });
    }

    // When department changes
    departmentSelect.addEventListener("change", () => {
        loadCourses(departmentSelect.value);
    });

    // Initial load with selected course
    loadCourses(departmentSelect.value, <?= $data['course_id'] ?? 'null'; ?>);
</script>
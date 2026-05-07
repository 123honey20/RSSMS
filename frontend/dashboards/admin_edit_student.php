<?php
// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Student ID");
}

$departmentsQuery = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$user_id = (int) $_GET['id'];
$error = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = trim($_POST['school_id']);
    $email = trim($_POST['email']);
    $thesis_title = trim($_POST['thesis_title']);
    $control_number = trim($_POST['control_number']);
    $research_leader = trim($_POST['research_leader']);
    $department_id = $_POST['department_id'];
    $course_id     = $_POST['course_id'];

    $checkDup = $conn->prepare("SELECT id FROM users WHERE (school_id = ? OR email = ?) AND id != ?");
    $checkDup->bind_param("ssi", $school_id, $email, $user_id);
    $checkDup->execute();
    
    if ($checkDup->get_result()->num_rows > 0) {
        $error = "This ID Number or Email is already in use by another account.";
    } else {
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
            $stmt2 = $conn->prepare("
            UPDATE students SET thesis_title = ?, control_number = ?, research_leader = ?, department_id = ?, course_id = ?
            WHERE user_id = ?
            ");
            $stmt2->bind_param(
                "sssiii",
                $thesis_title, $control_number, $research_leader, $department_id, $course_id, $user_id
            );
            $stmt2->execute();
        } else {
            $sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
            $active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

            $stmt2 = $conn->prepare("
                INSERT INTO students (user_id, thesis_title, control_number, research_leader, department_id, course_id, school_year)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param(
                "isssiis",
                $user_id, $thesis_title, $control_number, $research_leader, $department_id, $course_id, $active_sy
            );
            $stmt2->execute();
        }

        echo "<script>window.location.href = '../dashboards/admin_dashboard.php?page=students&updated=1';</script>";
        exit();
    }
}

$stmt = $conn->prepare("
    SELECT u.school_id, u.email,
           s.thesis_title, s.control_number, s.research_leader,
           s.department_id, s.course_id, s.school_year
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

<div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm max-w-3xl mx-auto border border-gray-100 dark:border-warmdark-border transition-colors duration-200">
    <div class="mb-8 border-b border-gray-200 dark:border-warmdark-border pb-4 transition-colors">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Edit Student Profile</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update account and research details for <?= htmlspecialchars($data['school_id']) ?></p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 mb-6 rounded-lg border border-red-100 dark:border-red-900/30 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">ID Number</label>
                <input type="text" name="school_id" required 
                    value="<?php echo htmlspecialchars($_POST['school_id'] ?? $data['school_id']); ?>" 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" name="email" required 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? $data['email']); ?>" 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 transition-colors">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Title</label>
            <input type="text" name="thesis_title" required
                value="<?php echo htmlspecialchars($_POST['thesis_title'] ?? $data['thesis_title'] ?? ''); ?>" 
                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Control Number</label>
                <input type="text" name="control_number" required
                    value="<?php echo htmlspecialchars($_POST['control_number'] ?? $data['control_number'] ?? ''); ?>" 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Research Leader</label>
                <input type="text" name="research_leader" required
                    value="<?php echo htmlspecialchars($_POST['research_leader'] ?? $data['research_leader'] ?? ''); ?>" 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 grid grid-cols-1 md:grid-cols-2 gap-5 transition-colors">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Department</label>
                <select name="department_id" id="studentDepartment" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
                    <option value="">Select Department...</option>
                    <?php 
                    $departmentsQuery->data_seek(0);
                    $selected_dept = $_POST['department_id'] ?? $data['department_id'];
                    while ($row = $departmentsQuery->fetch_assoc()): ?>
                        <option value="<?= $row['id']; ?>" <?= ($selected_dept == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Course</label>
                <select name="course_id" id="studentCourse" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
                    <option value="">Select Course...</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <a href="admin_dashboard.php?page=students" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg hover:bg-gray-200 dark:hover:bg-warmdark-border transition-all border border-transparent dark:border-warmdark-border">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 dark:bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 dark:hover:bg-blue-700 hover:shadow-lg transition-all">
                Update Student
            </button>
        </div>
    </form>
</div>

<script>
    const departmentSelect = document.getElementById("studentDepartment");
    const courseSelect = document.getElementById("studentCourse");

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

    departmentSelect.addEventListener("change", () => loadCourses(departmentSelect.value));
    
    let initialCourseId = <?= json_encode($_POST['course_id'] ?? $data['course_id'] ?? null); ?>;
    loadCourses(departmentSelect.value, initialCourseId);
</script>
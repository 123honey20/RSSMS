<?php
require_once "../../backend/config/database.php";

$error = "";
$success = "";

// Fetch the current active school year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id       = trim($_POST['school_id']);
    $email           = trim($_POST['email']);
    $password_raw    = $_POST['password'];
    $confirm_pass    = $_POST['confirm_password'];

    $thesis_title    = trim($_POST['thesis_title']);
    $control_number  = trim($_POST['control_number']);
    $research_leader = trim($_POST['research_leader']);
    $department_id   = trim($_POST['department_id']);
    $course_id       = trim($_POST['course_id']);
    $school_year     = $active_sy;

    $role   = "student";
    $status = "Approved";

    if ($password_raw !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR school_id = ?");
        $check->bind_param("ss", $email, $school_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email or ID Number already exists.";
        } else {
            $hashedPassword = password_hash($password_raw, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $school_id, $email, $hashedPassword, $role, $status);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                $stmt2 = $conn->prepare("
                    INSERT INTO students (user_id, thesis_title, control_number, research_leader, department_id, course_id, school_year)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt2->bind_param(
                    "isssiis",
                    $user_id, $thesis_title, $control_number, $research_leader, $department_id, $course_id, $school_year
                );
                
                if ($stmt2->execute()) {
                    $success = "Student added successfully!";
                } else {
                    $error = "Failed to save student profile.";
                }
            } else {
                $error = "Failed to create user account.";
            }
        }
    }
}
?>

<div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm max-w-3xl mx-auto border border-gray-100 dark:border-warmdark-border transition-colors duration-200">
    <div class="mb-8 border-b border-gray-200 dark:border-warmdark-border pb-4 transition-colors">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Add New Student</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a student account and assign their research details.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 mb-6 rounded-lg border border-red-100 dark:border-red-900/30 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 p-4 mb-6 rounded-lg border border-green-100 dark:border-green-900/30 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">ID Number</label>
                <input type="text" name="school_id" placeholder="Enter Student ID Number" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
                <input type="email" name="email" placeholder="Enter Student Email" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" placeholder="••••••••" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 transition-colors">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Thesis Title</label>
            <input type="text" name="thesis_title" placeholder="Complete Title of the Research Study" required 
                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Control Number</label>
                <input type="text" name="control_number" placeholder="Enter Student Control No." required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Research Leader</label>
                <input type="text" name="research_leader" placeholder="Enter Student Full Name" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 grid grid-cols-1 md:grid-cols-2 gap-5 transition-colors">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Department</label>
                <select name="department_id" id="departmentSelect" onchange="loadCourses(this.value)" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
                    <option value="">Select Department...</option>
                    <?php
                    $departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
                    while ($d = $departments->fetch_assoc()):
                    ?>
                        <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Course</label>
                <select name="course_id" id="courseSelect" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
                    <option value="">Select Course...</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <a href="../dashboards/admin_dashboard.php?page=students" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg hover:bg-gray-200 dark:hover:bg-warmdark-border transition-all border border-transparent dark:border-warmdark-border">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 dark:bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 dark:hover:bg-blue-700 hover:shadow-lg transition-all">
                Create Student
            </button>
        </div>
    </form>
</div>

<script>
    function loadCourses(departmentId) {
        const courseSelect = document.getElementById('courseSelect');
        courseSelect.innerHTML = '<option value="">Loading...</option>';

        if (!departmentId) {
            courseSelect.innerHTML = '<option value="">Select Course...</option>';
            return;
        }

        fetch('../../backend/ajax/get_courses.php?department_id=' + departmentId)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Select Course...</option>';
                data.forEach(course => {
                    options += `<option value="${course.id}">${course.name}</option>`;
                });
                courseSelect.innerHTML = options;
            })
            .catch(error => {
                courseSelect.innerHTML = '<option value="">Error loading courses</option>';
            });
    }
</script>
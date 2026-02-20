<?php
require_once "../../backend/config/database.php";

$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id       = trim($_POST['school_id']);
    $email           = trim($_POST['email']);
    $password_raw    = $_POST['password'];
    $confirm_pass    = $_POST['confirm_password'];

    $thesis_title    = trim($_POST['thesis_title']);
    $control_number  = trim($_POST['control_number']);
    $research_leader = trim($_POST['research_leader']);
    $department_id      = trim($_POST['department_id']);
    $course_id          = trim($_POST['course_id']);

    $role   = "student";
    $status = "Approved";

    if ($password_raw !== $confirm_pass) {
        $error = "Passwords do not match";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR school_id = ?");
        $check->bind_param("ss", $email, $school_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email or School ID already exists";
        } else {
            // Insert user
            $hashedPassword = password_hash($password_raw, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $school_id, $email, $hashedPassword, $role, $status);


            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Insert student profile
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
                if ($stmt2->execute()) {
                    $success = "Student added successfully!";
                } else {
                    $error = "Failed to save student profile";
                }
            } else {
                $error = "Failed to create user account";
            }
        }
    }
}
?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Add Student</h1>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-2 mb-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-2 mb-3 rounded">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-3">

        <input type="text" name="school_id" placeholder="School ID" required class="w-full border p-2 rounded">
        <input type="email" name="email" placeholder="Email" required class="w-full border p-2 rounded">
        <input type="password" name="password" placeholder="Enter Password" required class="w-full border p-2 rounded">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required class="w-full border p-2 rounded">

        <input type="text" name="control_number" placeholder="Control Number" required class="w-full border p-2 rounded">
        <input type="text" name="thesis_title" placeholder="Thesis Title" required class="w-full border p-2 rounded">
        <input type="text" name="research_leader" placeholder="Research Leader" required class="w-full border p-2 rounded">

        <select name="department_id" id="departmentSelect"
            onchange="loadCourses(this.value)"
            required class="w-full border p-2 rounded">

            <option value="">Select Department</option>
            <?php
            $departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
            while ($d = $departments->fetch_assoc()):
            ?>
                <option value="<?= $d['id']; ?>">
                    <?= htmlspecialchars($d['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="course_id" id="courseSelect"
            required class="w-full border p-2 rounded">

            <option value="">Select Course</option>
        </select>




        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=students" class="text-gray-600 hover:underline">Cancel</a>
            <button type="submit" class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">Save Student</button>
        </div>
    </form>
</div>

<script>
    function loadCourses(departmentId) {

        const courseSelect = document.getElementById('courseSelect');
        courseSelect.innerHTML = '<option value="">Loading...</option>';

        if (!departmentId) {
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            return;
        }

        fetch('../../backend/ajax/get_courses.php?department_id=' + departmentId)
            .then(response => response.json())
            .then(data => {

                let options = '<option value="">Select Course</option>';

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
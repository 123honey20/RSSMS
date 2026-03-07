<?php
require_once "../../backend/config/database.php";

$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id       = trim($_POST['school_id']);
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $confirm_pass    = $_POST['confirm_password'];

    $full_name       = trim($_POST['full_name']);
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : NULL;
    $service_role    = trim($_POST['service_role']);

    $role   = "personnel";
    $status = "Approved";

    if ($password !== $confirm_pass) {
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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (school_id, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $school_id, $email, $hashedPassword, $role, $status);


            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Insert personnel profile
                $stmt2 = $conn->prepare("
                    INSERT INTO personnel (user_id, full_name, department_id, service_role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt2->bind_param(
                    "isis",
                    $user_id,
                    $full_name,
                    $department_id,
                    $service_role
                );
                if ($stmt2->execute()) {
                    $success = "Personnel added successfully!";
                } else {
                    $error = "Failed to save personnel profile";
                }
            } else {
                $error = "Failed to create user account";
            }
        }
    }
}
?>

<div class="bg-white p-6 rounded-xl shadow max-w-xl mx-auto">
    <h1 class="text-xl font-bold mb-4">Add Personnel</h1>

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
        <input type="text" name="full_name" placeholder="Full Name" required class="w-full border p-2 rounded">

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

        <select name="service_role" id="serviceRole" required class="w-full border p-2 rounded">
            <option value="">Select Service Role</option>
            <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
            <option value="Human Grammarian">Human Grammarian</option>
            <option value="Statistician">Statistician</option>
            <option value="Librarian">Librarian</option>
            <option value="Ethics">Ethics</option>
        </select>

        <div class="flex justify-between pt-4">
            <a href="../dashboards/admin_dashboard.php?page=personnel" class="text-gray-600 hover:underline">Cancel</a>
            <button type="submit" class="bg-blue-900 text-white px-4 py-2 rounded hover:bg-blue-800">Save Student</button>
        </div>
    </form>
</div>

<script>
    const roleSelect = document.getElementById("serviceRole");
    const departmentSelect = document.getElementById("departmentSelect");

    function toggleDepartment() {

        const role = roleSelect.value;

        if (role === "Grammarly & AI Checking" || role === "Human Grammarian") {

            departmentSelect.value = "";
            departmentSelect.disabled = true;
            departmentSelect.removeAttribute("required");

        } else {

            departmentSelect.disabled = false;
            departmentSelect.setAttribute("required", "required");

        }
    }

    roleSelect.addEventListener("change", toggleDepartment);

    // run on page load
    toggleDepartment();
</script>
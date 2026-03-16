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
    $service_role    = trim($_POST['service_role']);
    
    // Force department to NULL if role is Grammarly & AI Checking
    if ($service_role === 'Grammarly & AI Checking') {
        $department_id = NULL;
    } else {
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : NULL;
    }

    $role   = "personnel";
    $status = "Approved";

    if ($password !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR school_id = ?");
        $check->bind_param("ss", $email, $school_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email or School ID already exists.";
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
                $stmt2->bind_param("isis", $user_id, $full_name, $department_id, $service_role);
                
                if ($stmt2->execute()) {
                    $success = "Personnel added successfully!";
                } else {
                    $error = "Failed to save personnel profile.";
                }
            } else {
                $error = "Failed to create user account.";
            }
        }
    }
}
?>

<div class="bg-white p-8 rounded-2xl shadow-sm max-w-3xl mx-auto border border-gray-100">
    <div class="mb-8 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Add New Personnel</h1>
        <p class="text-sm text-gray-500 mt-1">Create a staff account and assign their research service role.</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 p-4 mb-6 rounded-lg border border-red-100 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 p-4 mb-6 rounded-lg border border-green-100 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm"><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Full Name</label>
                <input type="text" name="full_name" placeholder="Enter Personnel Full Name" required 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">School ID</label>
                <input type="text" name="school_id" placeholder="Enter Personnel School ID" required 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
            <input type="email" name="email" placeholder="Enter Personel Email" required 
                class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" placeholder="••••••••" required 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6 mt-2 grid grid-cols-1 md:grid-cols-2 gap-5">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Service Role</label>
                <select name="service_role" id="serviceRole" required 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
                    <option value="">Select Role...</option>
                    <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                    <option value="Human Grammarian">Human Grammarian</option>
                    <option value="Statistician">Statistician</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Ethics">Ethics</option>
                </select>
            </div>

            <div id="deptContainer" class="hidden transition-all duration-300">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Assigned Department</label>
                <select name="department_id" id="departmentSelect" 
                    class="w-full border border-gray-300 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all">
                    <option value="">Select Department...</option>
                    <?php
                    $departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
                    while ($d = $departments->fetch_assoc()):
                    ?>
                        <option value="<?= $d['id']; ?>">
                            <?= htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100">
            <a href="../dashboards/admin_dashboard.php?page=personnel" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 hover:shadow-lg transition-all">
                Create Account
            </button>
        </div>
    </form>
</div>

<script>
    const roleSelect = document.getElementById("serviceRole");
    const deptContainer = document.getElementById("deptContainer");
    const departmentSelect = document.getElementById("departmentSelect");

    function toggleDepartment() {
        const role = roleSelect.value;

        // If it's Grammarly, HIDE the department dropdown entirely
        if (role === "Grammarly & AI Checking") {
            deptContainer.classList.add("hidden");
            departmentSelect.value = "";
            departmentSelect.removeAttribute("required");
        } 
        // If no role is selected yet, hide it to keep it clean
        else if (role === "") {
            deptContainer.classList.add("hidden");
            departmentSelect.removeAttribute("required");
        } 
        // For the other 4 roles, SHOW it and make it required
        else {
            deptContainer.classList.remove("hidden");
            departmentSelect.setAttribute("required", "required");
        }
    }

    roleSelect.addEventListener("change", toggleDepartment);
    
    toggleDepartment();
</script>
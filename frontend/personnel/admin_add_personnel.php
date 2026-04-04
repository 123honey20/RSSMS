<?php
require_once "../../backend/config/database.php";

$error = "";
$success = "";

// Fetch Departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id       = trim($_POST['school_id']);
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $confirm_pass    = $_POST['confirm_password'];

    $full_name       = trim($_POST['full_name']);
    $service_role    = trim($_POST['service_role']);
    
    // NEW: Capture array of departments
    $selected_departments = isset($_POST['departments']) ? $_POST['departments'] : [];
    
    // Force primary department to NULL if role is Grammarly & AI Checking, else use the first chosen
    if ($service_role === 'Grammarly & AI Checking') {
        $primary_dept = NULL;
    } else {
        $primary_dept = !empty($selected_departments) ? intval($selected_departments[0]) : NULL;
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
                $stmt2->bind_param("isis", $user_id, $full_name, $primary_dept, $service_role);
                
                if ($stmt2->execute()) {
                    
                    // NEW: Insert into Junction Table
                    if ($service_role !== 'Grammarly & AI Checking' && !empty($selected_departments)) {
                        $stmt3 = $conn->prepare("INSERT INTO personnel_departments (user_id, department_id) VALUES (?, ?)");
                        foreach ($selected_departments as $d_id) {
                            $d_id = intval($d_id);
                            $stmt3->bind_param("ii", $user_id, $d_id);
                            $stmt3->execute();
                        }
                        $stmt3->close();
                    }

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

<div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm max-w-3xl mx-auto border border-gray-100 dark:border-warmdark-border transition-colors duration-200">
    <div class="mb-8 border-b border-gray-200 dark:border-warmdark-border pb-4 transition-colors">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Add New Personnel</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a staff account and assign their research service role.</p>
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
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Full Name</label>
                <input type="text" name="full_name" placeholder="Enter Personnel Full Name" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">School ID</label>
                <input type="text" name="school_id" placeholder="Enter Personnel School ID" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
            <input type="email" name="email" placeholder="Enter Personel Email" required 
                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
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

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 grid grid-cols-1 md:grid-cols-2 gap-5 transition-colors">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Service Role</label>
                <select name="service_role" id="serviceRole" required 
                    class="w-full border border-gray-300 dark:border-warmdark-border px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
                    <option value="">Select Role...</option>
                    <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                    <option value="Human Grammarian">Human Grammarian</option>
                    <option value="Statistician">Statistician</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Ethics">Ethics</option>
                </select>
            </div>

            <div id="deptContainer" class="hidden transition-all duration-300">
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Assigned Departments</label>
                <div class="space-y-2 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-warmdark-border rounded-lg bg-gray-50 dark:bg-warmdark-bg shadow-inner">
                    <?php
                    $departments->data_seek(0); 
                    while ($d = $departments->fetch_assoc()): 
                    ?>
                        <label class="flex items-center gap-3 cursor-pointer p-1 hover:bg-gray-200 dark:hover:bg-warmdark-hover rounded transition-colors">
                            <input type="checkbox" name="departments[]" value="<?= $d['id'] ?>" 
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-200 font-medium"><?= htmlspecialchars($d['name']); ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 italic">* You can select multiple departments.</p>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <a href="../dashboards/admin_dashboard.php?page=personnel" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg hover:bg-gray-200 dark:hover:bg-warmdark-border transition-all border border-transparent dark:border-warmdark-border">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 dark:bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 dark:hover:bg-blue-700 hover:shadow-lg transition-all">
                Create Account
            </button>
        </div>
    </form>
</div>

<script>
    const roleSelect = document.getElementById("serviceRole");
    const deptContainer = document.getElementById("deptContainer");

    function toggleDepartment() {
        const role = roleSelect.value;

        // HIDE if Grammarly or empty, SHOW for everyone else
        if (role === "Grammarly & AI Checking" || role === "") {
            deptContainer.classList.add("hidden");
        } else {
            deptContainer.classList.remove("hidden");
        }
    }

    roleSelect.addEventListener("change", toggleDepartment);
    toggleDepartment();
</script>
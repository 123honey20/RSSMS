<?php
// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Personnel ID");
}

// Fetch all departments
$deptStmt = $conn->prepare("SELECT id, name FROM departments ORDER BY name ASC");
$deptStmt->execute();
$departmentsQuery = $deptStmt->get_result();

$user_id = (int) $_GET['id'];
$error = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = trim($_POST['school_id']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    
    // Get array of selected departments
    $selected_departments = isset($_POST['departments']) ? $_POST['departments'] : [];
    // Fallback: save the first selected department to the original column to satisfy existing foreign keys
    $primary_dept = !empty($selected_departments) ? $selected_departments[0] : null;

    $checkDup = $conn->prepare("SELECT id FROM users WHERE (school_id = ? OR email = ?) AND id != ?");
    $checkDup->bind_param("ssi", $school_id, $email, $user_id);
    $checkDup->execute();
    
    if ($checkDup->get_result()->num_rows > 0) {
        $error = "This ID Number or Email is already in use by another account.";
    } else {
        $roleStmt = $conn->prepare("SELECT service_role FROM personnel WHERE user_id = ?");
        $roleStmt->bind_param("i", $user_id);
        $roleStmt->execute();
        $service_role = $roleStmt->get_result()->fetch_assoc()['service_role'] ?? null;

        $stmt1 = $conn->prepare("UPDATE users SET school_id = ?, email = ? WHERE id = ?");
        $stmt1->bind_param("ssi", $school_id, $email, $user_id);
        $stmt1->execute();

        $check = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();

        // REMOVED GRAMMARLY EXCEPTION: Now saves department_id for all roles
        if ($check->get_result()->num_rows > 0) {
            $stmt2 = $conn->prepare("UPDATE personnel SET full_name = ?, department_id = ? WHERE user_id = ?");
            $stmt2->bind_param("sii", $full_name, $primary_dept, $user_id);
            $stmt2->execute();
        } else {
            $stmt2 = $conn->prepare("INSERT INTO personnel (user_id, full_name, department_id) VALUES (?, ?, ?)");
            $stmt2->bind_param("isi", $user_id, $full_name, $primary_dept);
            $stmt2->execute();
        }

        // === JUNCTION TABLE SYNC ===
        // REMOVED GRAMMARLY EXCEPTION: Now syncs departments for all roles
        // 1. Delete old mappings
        $delStmt = $conn->prepare("DELETE FROM personnel_departments WHERE user_id = ?");
        $delStmt->bind_param("i", $user_id);
        $delStmt->execute();

        // 2. Insert new mappings
        if (!empty($selected_departments)) {
            $insStmt = $conn->prepare("INSERT INTO personnel_departments (user_id, department_id) VALUES (?, ?)");
            foreach ($selected_departments as $dept_id) {
                $insStmt->bind_param("ii", $user_id, $dept_id);
                $insStmt->execute();
            }
        }

        echo "<script>window.location.href = '../dashboards/admin_dashboard.php?page=personnel&updated=1';</script>";
        exit();
    }
}

// Fetch Personnel data
$stmt = $conn->prepare("
    SELECT u.school_id, u.email,
           s.full_name, s.service_role
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Personnel not found");

// Fetch assigned departments from the Junction Table
$assigned_depts = [];
$deptMapStmt = $conn->prepare("SELECT department_id FROM personnel_departments WHERE user_id = ?");
$deptMapStmt->bind_param("i", $user_id);
$deptMapStmt->execute();
$resMap = $deptMapStmt->get_result();
while ($rowMap = $resMap->fetch_assoc()) {
    $assigned_depts[] = $rowMap['department_id'];
}
?>

<div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm max-w-3xl mx-auto border border-gray-100 dark:border-warmdark-border transition-colors duration-200">
    <div class="mb-8 border-b border-gray-200 dark:border-warmdark-border pb-4 transition-colors">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Edit Personnel Profile</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update account details for <?= htmlspecialchars($data['full_name']) ?></p>
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
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Full Name</label>
                <input type="text" name="full_name" required
                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? $data['full_name'] ?? ''); ?>"
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">ID Number</label>
                <input type="text" name="school_id" required
                    value="<?php echo htmlspecialchars($_POST['school_id'] ?? $data['school_id']); ?>"
                    class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
            <input type="email" name="email" required
                value="<?php echo htmlspecialchars($_POST['email'] ?? $data['email']); ?>"
                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-lg text-sm focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 focus:outline-none transition-all shadow-sm">
        </div>

        <div class="border-t border-gray-100 dark:border-warmdark-border pt-6 mt-2 grid grid-cols-1 md:grid-cols-2 gap-5 transition-colors">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Service Role</label>
                <input type="text" value="<?php echo htmlspecialchars($data['service_role']); ?>" 
                    class="w-full border border-blue-200 dark:border-blue-900/50 px-4 py-2.5 rounded-lg text-sm font-bold bg-blue-50 dark:bg-blue-900/10 text-blue-800 dark:text-blue-400 focus:outline-none cursor-not-allowed" readonly>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Assigned Departments</label>
                <div class="space-y-2 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-warmdark-border rounded-lg bg-gray-50 dark:bg-warmdark-bg shadow-inner">
                    <?php
                    $departmentsQuery->data_seek(0); 
                    while ($row = $departmentsQuery->fetch_assoc()): 
                        $isChecked = in_array($row['id'], $assigned_depts) ? 'checked' : '';
                    ?>
                        <label class="flex items-center gap-3 cursor-pointer p-1 hover:bg-gray-200 dark:hover:bg-warmdark-hover rounded transition-colors">
                            <input type="checkbox" name="departments[]" value="<?= $row['id'] ?>" <?= $isChecked ?> 
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-200 font-medium"><?= htmlspecialchars($row['name']); ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 italic">* You can select multiple departments.</p>
            </div>
            
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <a href="admin_dashboard.php?page=personnel" 
                class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg hover:bg-gray-200 dark:hover:bg-warmdark-border transition-all border border-transparent dark:border-warmdark-border">
                Cancel
            </a>
            <button type="submit" 
                class="bg-blue-700 dark:bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-blue-800 dark:hover:bg-blue-700 hover:shadow-lg transition-all">
                Update Personnel
            </button>
        </div>
    </form>
</div>
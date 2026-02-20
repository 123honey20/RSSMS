<?php
// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Personnel ID");
}
// Fetch all departments
$departmentsQuery = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

$user_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $department_id = $_POST['department_id'];

    // Update users table
    $stmt1 = $conn->prepare("UPDATE users SET school_id = ?, email = ? WHERE id = ?");
    $stmt1->bind_param("ssi", $school_id, $email, $user_id);
    $stmt1->execute();

    // Check if personnel row exists
    $check = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Update personnel table
        $stmt2 = $conn->prepare("
        UPDATE personnel SET full_name = ?, department_id = ? 
        WHERE user_id = ?
        ");
        $stmt2->bind_param("sii", $full_name, $department_id, $user_id);
        $stmt2->execute();
    } else {
        // Insert if not exists
        $stmt2 = $conn->prepare("
            INSERT INTO personnel (user_id, full_name, department_id)
            VALUES (?, ?, ?)
        ");
        $stmt2->bind_param(
            "isi",
            $user_id,
            $full_name,
            $department_id
        );
        $stmt2->execute();
    }

    echo "<script>
    window.location.href = '../dashboards/admin_dashboard.php?page=personnel&updated=1';
    </script>";
    exit();
}

// Fetch Personnel data
$stmt = $conn->prepare("
    SELECT u.school_id, u.email,
           s.full_name, s.department_id
    FROM users u
    LEFT JOIN personnel s ON u.id = s.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Personnel not found");
}
?>
<div class="bg-white p-6 rounded-xl shadow max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-6">Edit Personnel Profile</h2>

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
            <label class="block text-sm font-medium">Full Name</label>
            <input type="text" name="full_name"
                value="<?php echo htmlspecialchars($data['full_name']); ?>"
                class="w-full border rounded px-3 py-2">
        </div>

        <label class="block text-sm font-medium">Department</label>
        <select name="department_id" required
            class="w-full border rounded px-3 py-2">
            <option value="">Select Department</option>
            <?php
            $departmentsQuery->data_seek(0); // reset pointer
            while ($row = $departmentsQuery->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>"
                    <?= ($data['department_id'] == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>


        <div class="flex justify-end gap-2 pt-4">
            <a href="admin_dashboard.php?page=personnel"
                class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                Cancel
            </a>
            <button type="submit"
                class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">
                Update Personnel
            </button>
        </div>
    </form>
</div>
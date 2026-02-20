<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// Get student id
$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Check if already has submission
$sub = $conn->query("SELECT * FROM ethics WHERE student_id = $student_id");
$existing = $sub->fetch_assoc();
?>
<div class="flex-1">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="fixed top-5 right-5 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50 transition-opacity duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
            <span><?php echo $_SESSION['flash_success']; ?></span>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-success');
                if (toast) {
                    toast.classList.add('opacity-0');
                    setTimeout(() => toast.remove(), 500);
                }
            }, 3000);
        </script>

        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="bg-white shadow p-4">
        <h1 class="text-xl font-semibold">Upload Submission</h1>
        <p class="text-sm text-gray-600">Welcome, <?php echo $_SESSION['school_id']; ?></p>
    </div>

    <div class="p-6">
        <?php if ($existing): ?>
            <p class="mb-2">Current Status:
                <strong><?php echo ucfirst($existing['status']); ?></strong>
            </p>
            <p class="mb-4 text-sm text-gray-600">
                You already have a submission. Uploading again will replace it.
            </p>
        <?php endif; ?>

        <form action="../../backend/actions/upload-submission/upload_ethics.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="file" name="submission_file" required class="w-full border p-2 rounded">

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <?php echo $existing ? "Re-upload Submission" : "Upload Submission"; ?>
            </button>
        </form>
    </div>
</div>

<script>
    toast.classList.add('opacity-0');
</script>
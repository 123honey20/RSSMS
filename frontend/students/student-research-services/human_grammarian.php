<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// Get student id
$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

$subs = $conn->query("
    SELECT * FROM human_grammarian 
    WHERE student_id = $student_id 
    ORDER BY round DESC, uploaded_at DESC
");

$latestRes = $conn->query("
    SELECT * FROM human_grammarian 
    WHERE student_id = $student_id 
    ORDER BY round DESC 
    LIMIT 1
");
$latest = $latestRes->fetch_assoc();

$currentRound = $latest ? (int)$latest['round'] : 0;
$currentStatus = $latest ? $latest['status'] : null;

?>

<div class="space-y-6">

    <div class="flex flex-wrap gap-6">

        <?php
        $canUploadNewRound = false;

        if (!$latest) {
            $canUploadNewRound = true; // no submission yet
        } elseif ($currentStatus === 'Rejected' && $currentRound < 7) {
            $canUploadNewRound = true; // can go to next round
        }
        ?>

        <?php if ($currentStatus === 'Approved'): ?>
            <div class="bg-gray-200 rounded-lg p-5 w-64 flex items-center justify-between cursor-not-allowed opacity-60">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-gray-800">Upload Disabled</p>
                </div>
                <div class="text-2xl font-bold">+</div>
            </div>

        <?php elseif ($canUploadNewRound): ?>
            <a href="student_dashboard.php?page=student_upload_human_grammarian"
                class="bg-white shadow rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-gray-800">Upload Submission</p>
                </div>
                <div class="text-2xl font-bold">+</div>
            </a>

        <?php else: ?>
            <div class="bg-gray-200 rounded-lg p-5 w-64 flex items-center justify-between cursor-not-allowed opacity-60">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-gray-800">Upload Disabled</p>
                </div>
                <div class="text-2xl font-bold">⏳</div>
            </div>
        <?php endif; ?>


        <a href="#"
            class="bg-white shadow rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition">
            <div>
                <p class="text-sm text-gray-500">View</p>
                <p class="font-semibold text-gray-800">View Approved Result</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                         -1.274 4.057-5.064 7-9.542 7
                         -4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-md font-semibold text-gray-800 mb-4">
            History of All File Submit in Human Grammarian
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs uppercase text-gray-500 border-b">
                    <tr>
                        <th class="py-2">Round</th>
                        <th class="py-2">File</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Date</th>
                        <th class="py-2">Reports</th>
                        <th class="py-2 text-center">File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subs->num_rows > 0): ?>
                        <?php $i = 1; ?>
                        <?php while ($row = $subs->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="py-3"><?php echo $i++; ?>.</td>

                                <td class="py-3 max-w-xs">
                                    <?php
                                    $fullName = basename($row['file_path']);

                                    ?>
                                    <span
                                        class="block truncate"
                                        title="<?php echo htmlspecialchars($fullName); ?>">
                                        <?php echo htmlspecialchars($fullName); ?>
                                    </span>
                                </td>

                                <td class="py-3">
                                    <?php
                                    $status = $row['status'];
                                    $color = "text-gray-600";
                                    if ($status === "Approved") $color = "text-green-600";
                                    if ($status === "Rejected") $color = "text-red-600";
                                    if ($status === "Pending")  $color = "text-yellow-600";
                                    ?>
                                    <span class="px-3 py-1 text-xs rounded-full text-green-700 font-semibold <?php echo $color; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <?php echo $row['uploaded_at']; ?>
                                </td>
                                <td class="py-3">
                                    <a href="#"
                                        class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                        View
                                    </a>
                                </td>
                                <td class="py-3 text-center">
                                    <?php
                                    $round = (int)$row['round'];
                                    $status = $row['status'];

                                    $canReuploadSameRound = ($status === 'Pending');
                                    $disabled = ($status === 'Rejected' || $status === 'Approved');
                                    ?>

                                    <?php if ($canReuploadSameRound): ?>
                                        <a href="student_dashboard.php?page=student_upload_human_grammarian"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                            Re-upload
                                        </a>

                                    <?php else: ?>
                                        <span class="bg-gray-300 text-gray-600 px-3 py-1 rounded text-xs cursor-not-allowed">
                                            Re-upload
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">
                                No submissions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
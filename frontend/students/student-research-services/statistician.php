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
    SELECT * FROM statistician 
    WHERE student_id = $student_id 
    ORDER BY round DESC, uploaded_at DESC
");

$latestRes = $conn->query("
    SELECT * FROM statistician 
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

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 w-64 flex items-center justify-between opacity-75 cursor-not-allowed">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-green-700">Upload Disabled</p>
                </div>
                <div class="text-green-600 bg-green-100 p-2 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

        <?php elseif ($canUploadNewRound): ?>

            <a href="student_dashboard.php?page=student_upload_statistician"
                class="bg-white shadow rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition group">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">Upload Submission</p>
                </div>
                <div class="text-blue-600 bg-blue-50 p-2 rounded-xl group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </a>

        <?php else: ?>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 w-64 flex items-center justify-between opacity-75 cursor-not-allowed">
                <div>
                    <p class="text-sm text-gray-500">Upload</p>
                    <p class="font-semibold text-gray-600">Pending Submission</p>
                </div>
                <div class="text-yellow-600 bg-yellow-100 p-2 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

        <?php endif; ?>


        <?php if ($currentStatus === 'Approved'): ?>
            <a href="student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $latest['id']; ?>"
                class="bg-white shadow-sm border border-green-200 border-l-4 border-l-green-500 rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition group cursor-pointer">
                <div>
                    <p class="text-xs text-green-600 font-bold uppercase tracking-wider mb-0.5">Available</p>
                    <p class="font-semibold text-gray-800 group-hover:text-green-700 transition">View Approved Result</p>
                </div>
                <div class="text-green-600 bg-green-50 p-2 rounded-full group-hover:bg-green-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </a>
        <?php else: ?>
            <div class="bg-gray-100 border border-gray-200 rounded-lg p-5 w-64 flex items-center justify-between opacity-70">
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Locked</p>
                    <p class="font-semibold text-gray-500">Result Not Available</p>
                </div>
                <div class="text-gray-400 bg-gray-200 p-2 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-md font-semibold text-gray-800 mb-4">
            History of All File Submit in Statistician
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs uppercase text-gray-500 border-b">
                    <tr>
                        <th class="py-2">Submission</th>
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
                                <td class="py-3 text-xs">Round <?php echo (int)$row['round']; ?></td>

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
                                    <span class="py-1 text-xs text-green-700 font-semibold <?php echo $color; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <?php echo $row['uploaded_at']; ?>
                                </td>
                                <td class="py-3">
                                    <?php if ($status === 'Approved' || $status === 'Rejected'): ?>
                                        <a href="student_dashboard.php?page=student_view_statistician_report&id=<?php echo $row['id']; ?>"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition shadow">
                                            View
                                        </a>
                                    <?php else: ?>
                                        <span class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-xs">
                                            View
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?php
                                    $round = (int)$row['round'];
                                    $status = $row['status'];

                                    $canReuploadSameRound = ($status === 'Pending');
                                    $disabled = ($status === 'Rejected' || $status === 'Approved');
                                    ?>

                                    <?php if ($canReuploadSameRound): ?>
                                        <a href="student_dashboard.php?page=student_upload_statistician"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                            Re-upload
                                        </a>

                                    <?php else: ?>
                                        <span class="bg-gray-300 text-gray-600 px-3 py-1 rounded text-xs">
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
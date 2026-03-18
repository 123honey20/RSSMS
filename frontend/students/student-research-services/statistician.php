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

<div class="space-y-6 transition-colors duration-200">

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

            <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-75 cursor-not-allowed transition-colors">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Upload</p>
                    <p class="font-semibold text-green-700 dark:text-green-500">Upload Disabled</p>
                </div>
                <div class="text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-500/20 p-2 rounded-xl transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

        <?php elseif ($canUploadNewRound): ?>

            <a href="student_dashboard.php?page=student_upload_statistician"
                class="bg-white dark:bg-warmdark-panel shadow dark:shadow-md rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-transparent dark:border-warmdark-border">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Upload</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Upload Submission</p>
                </div>
                <div class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl group-hover:scale-105 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </a>

        <?php else: ?>

            <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-75 cursor-not-allowed transition-colors">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Upload</p>
                    <p class="font-semibold text-gray-600 dark:text-gray-300">Pending Submission</p>
                </div>
                <div class="text-yellow-600 dark:text-yellow-500 bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded-xl transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

        <?php endif; ?>


        <?php if ($currentStatus === 'Approved'): ?>
            <a href="student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $latest['id']; ?>"
                class="bg-white dark:bg-warmdark-panel shadow-sm border border-green-200 dark:border-green-900/50 border-l-4 border-l-green-500 dark:border-l-green-500 rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition group cursor-pointer">
                <div>
                    <p class="text-xs text-green-600 dark:text-green-500 font-bold uppercase tracking-wider mb-0.5">Available</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">View Approved Result</p>
                </div>
                <div class="text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-500/10 p-2 rounded-full group-hover:bg-green-100 dark:group-hover:bg-green-500/20 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </a>
        <?php else: ?>
            <div class="bg-gray-100 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-70 transition-colors">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-0.5">Locked</p>
                    <p class="font-semibold text-gray-500 dark:text-gray-400">Result Not Available</p>
                </div>
                <div class="text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-warmdark-bg p-2 rounded-full transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-warmdark-panel rounded-lg shadow border border-transparent dark:border-warmdark-border p-6 transition-colors">
        <h2 class="text-md font-semibold text-gray-800 dark:text-gray-100 mb-4">
            History of All File Submit in Statistician
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                    <tr>
                        <th class="py-2">Submission</th>
                        <th class="py-2">File</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Date</th>
                        <th class="py-2">Reports</th>
                        <th class="py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                    <?php if ($subs->num_rows > 0): ?>
                        <?php $i = 1; ?>
                        <?php while ($row = $subs->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                                <td class="py-3 text-xs font-semibold dark:text-gray-200">Round <?php echo (int)$row['round']; ?></td>

                                <td class="py-3 max-w-xs">
                                    <?php
                                    $fullName = basename($row['file_path']);
                                    ?>
                                    <span
                                        class="block truncate text-gray-700 dark:text-gray-300"
                                        title="<?php echo htmlspecialchars($fullName); ?>">
                                        <?php echo htmlspecialchars($fullName); ?>
                                    </span>
                                </td>

                                <td class="py-3">
                                    <?php
                                    $status = $row['status'];
                                    $color = "text-gray-600 dark:text-gray-400";
                                    if ($status === "Approved") $color = "text-green-600 dark:text-green-400";
                                    if ($status === "Rejected") $color = "text-red-600 dark:text-red-400";
                                    if ($status === "Pending")  $color = "text-yellow-600 dark:text-yellow-400";
                                    ?>
                                    <span class="py-1 text-xs font-bold <?php echo $color; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                </td>
                                <td class="py-3">
                                    <?php if ($status === 'Approved' || $status === 'Rejected'): ?>
                                        <a href="student_dashboard.php?page=student_view_statistician_report&id=<?php echo $row['id']; ?>"
                                            class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors shadow-sm">
                                            View
                                        </a>
                                    <?php else: ?>
                                        <span class="bg-gray-200 dark:bg-warmdark-bg text-gray-500 dark:text-gray-500 px-3 py-1.5 rounded text-xs transition-colors cursor-not-allowed">
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
                                            class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors shadow-sm inline-block">
                                            Re-upload
                                        </a>

                                    <?php else: ?>
                                        <span class="bg-gray-200 dark:bg-warmdark-bg text-gray-500 dark:text-gray-500 px-3 py-1.5 rounded text-xs transition-colors inline-block cursor-not-allowed">
                                            Re-upload
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400 dark:text-gray-500">
                                No submissions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
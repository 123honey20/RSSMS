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

// Check if student has an assigned Grammarly & AI Checking personnel AND fetch their name
$appStmt = $conn->prepare("
    SELECT sa.*, p_assign.full_name as assigned_name
    FROM service_applications sa 
    LEFT JOIN personnel p_assign ON sa.assigned_personnel_id = p_assign.id
    WHERE sa.student_id = ? AND sa.service_type = 'Grammarly & AI Checking' 
    AND sa.status = 'Approved'
    ORDER BY sa.id DESC LIMIT 1
");
$appStmt->bind_param("i", $student_id);
$appStmt->execute();
$application = $appStmt->get_result()->fetch_assoc();
$appStmt->close();

$isAssigned = $application ? true : false;

// NEW: Check if student has ANY actual submissions so we can hide the fading alerts later
$hasAnySubmission = false;
if ($student_id) {
    $subCheck = $conn->query("SELECT id FROM grammarly_ai WHERE student_id = $student_id LIMIT 1");
    if ($subCheck && $subCheck->num_rows > 0) {
        $hasAnySubmission = true;
    }
}

$subs = $conn->query("
    SELECT * FROM grammarly_ai 
    WHERE student_id = $student_id 
    ORDER BY round DESC, uploaded_at DESC
");

$latestRes = $conn->query("
    SELECT * FROM grammarly_ai 
    WHERE student_id = $student_id 
    ORDER BY round DESC 
    LIMIT 1
");
$latest = $latestRes->fetch_assoc();

$currentRound = $latest ? (int)$latest['round'] : 0;
$currentStatus = $latest ? $latest['status'] : null;

// Fetch the specific requirements for Grammarly & AI
$req_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'req_desc_grammarly_ai'");
$grammarly_requirements_json = $req_stmt->fetch_assoc()['setting_value'] ?? '[]';
$grammarly_requirements = json_decode($grammarly_requirements_json, true);

// Fallback just in case it's still old plain text
if (!is_array($grammarly_requirements)) {
    $grammarly_requirements = array_filter(explode("\n", $grammarly_requirements_json));
}
?>

<div class="space-y-6 transition-colors duration-200">

    <?php if (!$isAssigned): ?>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-8 rounded-2xl shadow-sm border border-indigo-200 dark:border-indigo-900/50 max-w-2xl transition-colors">
            <div class="flex items-start sm:items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-indigo-900 dark:text-indigo-100">Waiting for Grammarly & AI Assignment</h2>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-2 leading-relaxed">
                        Your research profile is currently pending personnel assignment. The System Administrator will officially assign a dedicated Grammarly & AI Checker to your group soon.
                        Once assigned, your upload dashboard will automatically unlock here.
                    </p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <?php if (!$hasAnySubmission): ?>
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-init="setTimeout(() => show = false, 5000)" 
                 x-transition.opacity.duration.500ms 
                 class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border border-green-200 dark:border-green-900/30 flex items-center gap-3 max-w-4xl mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-sm text-green-800 dark:text-green-300 font-medium">A Grammarly & AI Checker has been assigned to you! You may now submit your documents for review.</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($grammarly_requirements) && $currentStatus !== 'Approved'): ?>
            <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-xl border border-blue-200 dark:border-blue-900/30 w-full">
                <h3 class="text-sm font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Required Documents for Grammarly & AI Checking
                </h3>
                <ul class="list-decimal list-inside text-sm text-blue-800 dark:text-blue-300 space-y-1.5 pl-2 font-medium">
                    <?php foreach ($grammarly_requirements as $req): ?>
                        <li><?php echo htmlspecialchars($req); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-6">

            <?php
            // Check if student has pending submission
            $hasPendingSubmission = $conn->query("
                SELECT id FROM grammarly_ai 
                WHERE student_id = $student_id 
                AND status = 'Pending'
            ")->num_rows > 0;

            // Get latest approved transaction
            $approvedTransactionRes = $conn->query("
                SELECT * FROM grammarly_ai_transactions
                WHERE student_id = $student_id
                AND status = 'Approved'
                ORDER BY round DESC
                LIMIT 1
            ");

            $approvedTransaction = $approvedTransactionRes->fetch_assoc();

            // Check if that approved transaction already has a submission
            $hasSubmissionForApprovedRound = false;

            if ($approvedTransaction) {
                $approvedRound = (int)$approvedTransaction['round'];

                $checkSubmission = $conn->query("
                SELECT id FROM grammarly_ai
                WHERE student_id = $student_id
                AND round = $approvedRound
            ");

                $hasSubmissionForApprovedRound = $checkSubmission->num_rows > 0;
            }

            // Determine if upload is allowed
            $canUploadSubmission = false;

            if ($approvedTransaction && !$hasSubmissionForApprovedRound) {
                $canUploadSubmission = true;
            }
            ?>

            <?php if ($currentStatus === 'Approved'): ?>

                <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-75 transition-colors">
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

            <?php elseif ($hasPendingSubmission): ?>

                <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-75 transition-colors">
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

            <?php elseif (!$canUploadSubmission): ?>

                <a href="student_dashboard.php?page=student_transaction_grammarly_ai"
                    class="bg-white dark:bg-warmdark-panel shadow dark:shadow-md rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-transparent dark:border-warmdark-border">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Upload</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Process Receipt</p>
                    </div>
                    <div class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl group-hover:scale-105 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                </a>

            <?php else: ?>

                <a href="student_dashboard.php?page=student_upload_grammarly_ai"
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

            <?php endif; ?>


            <?php if ($currentStatus === 'Approved'): ?>
                <a href="student_dashboard.php?page=student_grammarly_ai_approved_result&id=<?php echo $latest['id']; ?>"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($application && $application['assigned_name']): ?>
                <div class="bg-white dark:bg-warmdark-panel shadow-sm border border-indigo-200 dark:border-indigo-900/50 border-l-4 border-l-indigo-500 dark:border-l-indigo-500 rounded-lg p-5 w-64 flex items-center justify-between transition-colors">
                    <div class="overflow-hidden pr-2">
                        <p class="text-xs text-indigo-600 dark:text-indigo-500 font-bold uppercase tracking-wider mb-0.5">Assigned To</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 truncate text-sm" title="<?= htmlspecialchars($application['assigned_name']) ?>">
                            <?= htmlspecialchars($application['assigned_name']) ?>
                        </p>
                    </div>
                    <div class="text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 p-2 rounded-full shrink-0 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <div class="bg-white dark:bg-warmdark-panel rounded-lg shadow border border-transparent dark:border-warmdark-border p-6 transition-colors">
            <h2 class="text-md font-semibold text-gray-800 dark:text-gray-100 mb-4">
                History of All File Submit in Grammarly & AI Checking
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
                                            <a href="student_dashboard.php?page=student_view_grammarly_ai_report&id=<?php echo $row['id']; ?>"
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
                                            <a href="student_dashboard.php?page=student_upload_grammarly_ai"
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

    <?php endif; ?>
</div>
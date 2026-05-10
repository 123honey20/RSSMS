<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// 1. Get student id, department, AND course
$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id, department_id, course_id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];
$student_dept_id = $student['department_id'];
$student_course_id = $student['course_id'];

$appStmt = $conn->prepare("
    SELECT sa.*, 
           p_req.full_name as requested_name,
           p_assign.full_name as assigned_name,
           p_assign.service_role as assigned_role,
           sa.extra_rounds, sa.round_request_status
    FROM service_applications sa 
    LEFT JOIN personnel p_req ON sa.requested_personnel_id = p_req.id 
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

// --- GET ADMIN RULES FOR ROUNDS ---
$reqStmt = $conn->prepare("SELECT round_limit_per_phase FROM course_service_requirements WHERE course_id = ? AND service_type = 'Grammarly & AI Checking'");
$reqStmt->bind_param("i", $student_course_id);
$reqStmt->execute();
$reqRes = $reqStmt->get_result()->fetch_assoc();
$max_rounds = $reqRes ? (int)$reqRes['round_limit_per_phase'] : 7;
$reqStmt->close();

// Add granted extra rounds to the max rounds limit
if ($application) {
    $max_rounds += (int)$application['extra_rounds'];
}

// --- DYNAMIC REASSIGNMENT LOGIC ---
$origPersonnelName = $application['requested_name'] ?? null;
$currentPersonnelName = $application['assigned_name'] ?? null;

if ($student_id) {
    // Check the logs to find the absolute FIRST personnel assigned if reassigned
    $logQuery = $conn->query("
        SELECT p1.full_name as original_name 
        FROM reassignment_logs r
        JOIN personnel p1 ON r.from_personnel_id = p1.id
        WHERE r.student_id = $student_id AND r.service_type = 'Grammarly & AI Checking'
        ORDER BY r.reassigned_at ASC LIMIT 1
    ");
    if ($logQuery && $logQuery->num_rows > 0) {
        $logRes = $logQuery->fetch_assoc();
        $origPersonnelName = $origPersonnelName ?: $logRes['original_name'];
    }
}
$isReassigned = ($origPersonnelName && $origPersonnelName !== $currentPersonnelName);

$hasAnySubmission = false;
if ($student_id) {
    $subCheck = $conn->query("SELECT id FROM grammarly_ai WHERE student_id = $student_id LIMIT 1");
    if ($subCheck && $subCheck->num_rows > 0) {
        $hasAnySubmission = true;
    }
}

$subs = $conn->query("
    SELECT *, COALESCE(is_locked, 0) as is_locked FROM grammarly_ai 
    WHERE student_id = $student_id 
    ORDER BY round DESC, uploaded_at DESC
");

// Fetch Latest Document Status
$latestRes = $conn->query("
    SELECT *, COALESCE(is_locked, 0) as is_locked FROM grammarly_ai 
    WHERE student_id = $student_id 
    ORDER BY round DESC 
    LIMIT 1
");
$latest = $latestRes->fetch_assoc();
$currentRound = $latest ? (int)$latest['round'] : 0;
$currentStatus = $latest ? $latest['status'] : null;
$is_locked = $latest ? (int)$latest['is_locked'] : 0;

// Fetch Latest Transaction (Receipt) Status
$latestTransRes = $conn->query("
    SELECT status FROM grammarly_ai_transactions 
    WHERE student_id = $student_id 
    ORDER BY round DESC 
    LIMIT 1
");
$latestTrans = $latestTransRes->fetch_assoc();
$transStatus = $latestTrans ? $latestTrans['status'] : null;

// Fetch Requirements
$req_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'req_desc_grammarly_ai_{$student_course_id}'");
$grammarly_requirements_json = $req_stmt->fetch_assoc()['setting_value'] ?? '[]';
$grammarly_requirements = json_decode($grammarly_requirements_json, true);

if (!is_array($grammarly_requirements)) {
    $grammarly_requirements = array_filter(explode("\n", $grammarly_requirements_json));
}

// --- DYNAMIC RE-UPLOAD LOGIC (Smart Control) ---
$canUpdateReceipt = ($latestTrans && $transStatus !== 'Approved');
$canUpdateDoc = ($latest && $currentStatus === 'Pending' && $is_locked === 0);

$canUploadSubmission = false;
$receiptOnlyMode = false;
$needsRoundRequest = false;

if (!$latest) {
    $canUploadSubmission = true; 
} elseif ($currentStatus === 'Needs Revision' && $currentRound < $max_rounds) {
    $canUploadSubmission = true; 
} elseif ($currentStatus === 'Needs Revision' && $currentRound >= $max_rounds) {
    $needsRoundRequest = true; // Exhausted all rounds
} elseif ($transStatus === 'Needs Revision' && $currentStatus === 'Pending') {
    $receiptOnlyMode = true; 
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
            <div x-data="{ openReqs: false }" class="bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-900/30 w-full mb-6 overflow-hidden transition-colors">
                <button @click="openReqs = !openReqs" class="w-full p-4 sm:p-5 flex items-center justify-between text-left focus:outline-none hover:bg-blue-100/50 dark:hover:bg-blue-900/20 transition-colors">
                    <h3 class="text-sm font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider flex items-center gap-2 m-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Required Documents for Grammarly & AI Checking
                    </h3>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" :class="{'rotate-180': openReqs}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <div x-show="openReqs" x-collapse x-cloak>
                    <div class="px-5 pb-5 pt-1 border-t border-blue-100 dark:border-blue-900/30 mt-1">
                        <ul class="list-decimal list-inside text-sm text-blue-800 dark:text-blue-300 space-y-2 pl-2 font-medium mt-3">
                            <?php foreach ($grammarly_requirements as $req): ?>
                                <li><?php echo htmlspecialchars($req); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-6">

            <?php if ($currentStatus === 'Approved'): ?>
                <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-90 transition-colors">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <p class="font-semibold text-green-700 dark:text-green-500">Fully Completed</p>
                    </div>
                    <div class="text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-500/20 p-2 rounded-xl transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
            <?php elseif ($needsRoundRequest): ?>
                <div class="bg-red-50 dark:bg-warmdark-panel shadow-sm border border-red-200 dark:border-red-900/50 rounded-lg p-5 w-64 flex flex-col items-center justify-center text-center transition-colors">
                    <p class="text-xs font-bold text-red-600 dark:text-red-500 uppercase tracking-wider mb-3">Max Rounds Reached</p>
                    
                    <?php if ($application['round_request_status'] === 'Pending'): ?>
                        <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[11px] px-3 py-1.5 rounded-md font-bold border border-yellow-200 dark:border-yellow-800/50 w-full">
                            Request Pending...
                        </span>
                    <?php else: ?>
                        <form action="../../backend/actions/student/request_extra_round.php" method="POST" class="w-full">
                            <input type="hidden" name="service_type" value="Grammarly & AI Checking">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition">
                                Request Extra Round
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($receiptOnlyMode): ?>
                <a href="student_dashboard.php?page=student_upload_grammarly_ai&mode=receipt"
                    class="bg-red-50 dark:bg-red-900/10 shadow dark:shadow-md rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-red-200 dark:border-red-900/30">
                    <div>
                        <p class="text-sm text-red-500 dark:text-red-400">Action Required</p>
                        <p class="font-semibold text-red-700 dark:text-red-300 group-hover:text-red-800 transition-colors">Re-upload Receipt</p>
                    </div>
                    <div class="text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/40 p-2 rounded-xl group-hover:scale-105 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </a>

            <?php elseif ($canUploadSubmission): ?>
                <a href="student_dashboard.php?page=student_upload_grammarly_ai&mode=both"
                    class="bg-white dark:bg-warmdark-panel shadow dark:shadow-md rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-transparent dark:border-warmdark-border">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Upload</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Submit Files</p>
                    </div>
                    <div class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl group-hover:scale-105 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                </a>

            <?php elseif ($currentStatus === 'Pending'): ?>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PERSONNEL CARD (UPDATED WITH REASSIGNMENT DISPLAY) -->
            <?php if ($application && $application['assigned_name']): ?>
                <div class="bg-white dark:bg-warmdark-panel shadow-sm border border-indigo-200 dark:border-indigo-900/50 border-l-4 border-l-indigo-500 dark:border-l-indigo-500 rounded-lg p-5 w-64 flex items-center justify-between transition-colors">
                    <div class="overflow-hidden pr-2 flex-1 min-w-0">
                        
                        <?php if ($isReassigned): ?>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Original Personnel</p>
                            <p class="font-medium text-gray-500 dark:text-gray-400 truncate text-xs mb-2" title="<?= htmlspecialchars($origPersonnelName) ?>">
                                <?= htmlspecialchars($origPersonnelName) ?>
                            </p>
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider mb-0.5">Sub Personnel (Active)</p>
                        <?php else: ?>
                            <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider mb-0.5">Assigned To</p>
                        <?php endif; ?>

                        <p class="font-semibold text-gray-800 dark:text-gray-200 truncate text-sm" title="<?= htmlspecialchars($currentPersonnelName) ?>">
                            <?= htmlspecialchars($currentPersonnelName) ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" title="<?= htmlspecialchars($application['assigned_role'] ?? 'Grammarly & AI Checking') ?>">
                            <?= htmlspecialchars($application['assigned_role'] ?? 'Grammarly & AI Checking') ?>
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
                            <th class="py-2">Date & Time</th>
                            <th class="py-2 text-center">Report</th> 
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                        <?php if ($subs->num_rows > 0): ?>
                            <?php while ($row = $subs->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                                    <td class="py-3 text-xs font-semibold dark:text-gray-200">Round <?php echo (int)$row['round']; ?></td>
                                    <td class="py-3 max-w-xs">
                                        <?php $fullName = basename($row['file_path']); ?>
                                        <span class="block truncate text-gray-700 dark:text-gray-300" title="<?php echo htmlspecialchars($fullName); ?>">
                                            <?php echo htmlspecialchars($fullName); ?>
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <?php
                                        $status = $row['status'];
                                        $color = "text-gray-600 dark:text-gray-400";
                                        if ($status === "Approved") $color = "text-green-600 dark:text-green-400";
                                        if ($status === "Needs Revision") $color = "text-red-600 dark:text-red-400";
                                        if ($status === "Pending")  $color = "text-yellow-600 dark:text-yellow-400";
                                        ?>
                                        <span class="py-1 text-xs font-bold <?php echo $color; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- TIMELINE / DATE & TIME COLUMN -->
                                    <td class="py-3 text-xs whitespace-nowrap">
                                        <div class="flex flex-col gap-1.5">
                                            <div>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 block">Uploaded</span>
                                                <span class="text-gray-700 dark:text-gray-300 font-medium"><?php echo date('M d, Y \a\t h:i A', strtotime($row['uploaded_at'])); ?></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 block">Finalized</span>
                                                <?php if ($status === 'Approved' || $status === 'Needs Revision'): ?>
                                                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                                                        <?php 
                                                        $finalizedDate = $row['updated_at'] ?? null;
                                                        echo $finalizedDate ? date('M d, Y \a\t h:i A', strtotime($finalizedDate)) : '--'; 
                                                        ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-yellow-600 dark:text-yellow-500 italic font-medium">Waiting...</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- REPORT ACTIONS -->
                                    <td class="py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            
                                            <!-- View Button -->
                                            <?php if ($status === 'Approved' || $status === 'Needs Revision'): ?>
                                                <a href="student_dashboard.php?page=student_view_grammarly_ai_report&id=<?php echo $row['id']; ?>"
                                                    class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors shadow-sm font-bold inline-block">
                                                    View
                                                </a>
                                            <?php else: ?>
                                                <span class="bg-gray-100 dark:bg-warmdark-bg text-gray-400 dark:text-gray-600 border border-transparent px-3 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed inline-block">
                                                    View
                                                </span>
                                            <?php endif; ?>

                                            <!-- Re-upload / Locked Logic -->
                                            <?php 
                                            $isRowLatest = ($row['id'] === $latest['id']);
                                            $rowLocked = (int)$row['is_locked'] === 1;
                                            ?>
                                            
                                            <?php if ($isRowLatest && $status === 'Pending'): ?>
                                                <?php if ($rowLocked): ?>
                                                    <!-- LOCKED STATE UI -->
                                                    <span title="Personnel is currently reviewing this file" class="flex items-center gap-1.5 bg-gray-100 dark:bg-warmdark-bg text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-warmdark-border px-3 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed transition-colors shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                        </svg>
                                                        Locked
                                                    </span>
                                                <?php elseif ($canUpdateDoc || $canUpdateReceipt): ?>
                                                    <!-- ACTIVE REUPLOAD BUTTON -->
                                                    <button onclick="openReuploadChoiceModal()" class="flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Update
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Old rounds or Approved rounds -->
                                                <span class="bg-gray-50 dark:bg-warmdark-bg text-gray-400 dark:text-gray-600 px-4 py-1.5 rounded-lg text-xs font-bold border border-transparent cursor-not-allowed inline-block">
                                                    Reviewed
                                                </span>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-400 dark:text-gray-500">
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

<div id="reuploadChoiceModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[400px] max-w-[95%] rounded-2xl shadow-2xl p-6 space-y-4 border border-transparent dark:border-warmdark-border transition-colors">
        <div class="flex justify-between items-center border-b border-gray-100 dark:border-warmdark-border pb-3">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Select Re-upload Type</h3>
            <button onclick="closeReuploadChoiceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">What would you like to update? (This will replace your currently pending file).</p>
        
        <div class="flex flex-col gap-3 mt-4">
            <?php if ($canUpdateDoc): ?>
            <a href="student_dashboard.php?page=student_upload_grammarly_ai&mode=document" class="w-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-800/50 py-3 rounded-xl text-sm font-bold text-center transition-colors">
                Update Document Only
            </a>
            <?php endif; ?>
            
            <?php if ($canUpdateReceipt): ?>
            <a href="student_dashboard.php?page=student_upload_grammarly_ai&mode=receipt" class="w-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-800/50 py-3 rounded-xl text-sm font-bold text-center transition-colors">
                Update Receipt Only
            </a>
            <?php endif; ?>
            
            <?php if ($canUpdateDoc && $canUpdateReceipt): ?>
            <a href="student_dashboard.php?page=student_upload_grammarly_ai&mode=both" class="w-full bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 text-white shadow-md py-3 rounded-xl text-sm font-bold text-center transition-colors">
                Update Both Files
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function openReuploadChoiceModal() {
        document.getElementById('reuploadChoiceModal').classList.remove('hidden');
        document.getElementById('reuploadChoiceModal').classList.add('flex');
    }
    function closeReuploadChoiceModal() {
        document.getElementById('reuploadChoiceModal').classList.remove('flex');
        document.getElementById('reuploadChoiceModal').classList.add('hidden');
    }
</script>
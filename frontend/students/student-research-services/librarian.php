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

// --- GET ADMIN RULES FOR PHASES/ROUNDS (NOW USING COURSE) ---
$reqStmt = $conn->prepare("SELECT required_phases, round_limit_per_phase FROM course_service_requirements WHERE course_id = ? AND service_type = 'Librarian'");
$reqStmt->bind_param("i", $student_course_id);
$reqStmt->execute();
$reqRes = $reqStmt->get_result()->fetch_assoc();
$max_phases = $reqRes ? (int)$reqRes['required_phases'] : 1;
$max_rounds = $reqRes ? (int)$reqRes['round_limit_per_phase'] : 7;
$reqStmt->close();

// 2. Check the Service Application Status
$appStmt = $conn->prepare("
    SELECT sa.*, 
           p_req.full_name as requested_name,
           p_assign.full_name as assigned_name,
           p_assign.service_role as assigned_role,
           sa.extra_rounds, sa.round_request_status
    FROM service_applications sa 
    LEFT JOIN personnel p_req ON sa.requested_personnel_id = p_req.id 
    LEFT JOIN personnel p_assign ON sa.assigned_personnel_id = p_assign.id
    WHERE sa.student_id = ? AND sa.service_type = 'Librarian' 
    ORDER BY sa.id DESC LIMIT 1
");
$appStmt->bind_param("i", $student_id);
$appStmt->execute();
$application = $appStmt->get_result()->fetch_assoc();
$appStmt->close();

$appStatus = $application ? $application['status'] : null;
$isAssigned = $application ? true : false;
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
        WHERE r.student_id = $student_id AND r.service_type = 'Librarian'
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
    $subCheck = $conn->query("SELECT id FROM librarian WHERE student_id = $student_id LIMIT 1");
    if ($subCheck && $subCheck->num_rows > 0) {
        $hasAnySubmission = true;
    }
}

// 3. If Application is Approved, load the actual file submissions
$subs = null;
$latest = null;
$currentRound = 0;
$currentPhase = 1;
$currentStatus = null;
$is_locked = 0;

if ($appStatus === 'Approved') {
    $subs = $conn->query("SELECT *, COALESCE(is_locked, 0) as is_locked FROM librarian WHERE student_id = $student_id ORDER BY phase DESC, round DESC, uploaded_at DESC");

    $latestRes = $conn->query("SELECT *, COALESCE(is_locked, 0) as is_locked FROM librarian WHERE student_id = $student_id ORDER BY phase DESC, round DESC LIMIT 1");
    $latest = $latestRes->fetch_assoc();
    $currentRound = $latest ? (int)$latest['round'] : 0;
    $currentPhase = $latest ? (int)($latest['phase'] ?? 1) : 1;
    $currentStatus = $latest ? $latest['status'] : null;
    $is_locked = $latest ? (int)$latest['is_locked'] : 0;
}

// 4. Fetch the specific requirements for Librarian
$req_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'req_desc_librarian_{$student_course_id}'");
$librarian_requirements_json = $req_stmt->fetch_assoc()['setting_value'] ?? '[]';
$librarian_requirements = json_decode($librarian_requirements_json, true);

if (!is_array($librarian_requirements)) {
    $librarian_requirements = array_filter(explode("\n", $librarian_requirements_json));
}

// ==========================================
// CENTRALIZED LOGIC ALGORITHMS
// ==========================================
$isFullyCompleted = ($currentStatus === 'Approved' && $currentPhase >= $max_phases);
$canUploadNewRound = false;
$needsRoundRequest = false;

if (!$latest) {
    $canUploadNewRound = true; // no submission yet
} elseif ($currentStatus === 'Needs Revision' && $currentRound < $max_rounds) {
    $canUploadNewRound = true; // can go to next round
} elseif ($currentStatus === 'Approved' && $currentPhase < $max_phases) {
    $canUploadNewRound = true; // can go to next phase
} elseif ($currentStatus === 'Needs Revision' && $currentRound >= $max_rounds) {
    $needsRoundRequest = true; // Exhausted all rounds
}

// DYNAMIC RE-UPLOAD LOGIC
$canUpdateDoc = ($latest && $currentStatus === 'Pending' && $is_locked === 0);
?>

<div class="space-y-6 transition-colors duration-200">

    <?php if ($appStatus !== 'Approved'): ?>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-8 rounded-2xl shadow-sm border border-indigo-200 dark:border-indigo-900/50 max-w-2xl transition-colors">
            <div class="flex items-start sm:items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-indigo-900 dark:text-indigo-100">Waiting for Librarian Assignment</h2>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-2 leading-relaxed">
                        Your research profile is currently pending personnel assignment. The System Administrator will officially assign a dedicated Librarian to your group soon.
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
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-green-800 dark:text-green-300 font-medium">A Librarian has been assigned to you! You may now submit your documents for review.</span>
            </div>
        <?php endif; ?>

        <!-- REQUIREMENTS DROPDOWN: Visible until ALL phases are completed -->
        <?php if (!empty($librarian_requirements) && $appStatus === 'Approved' && !$isFullyCompleted): ?>
            <div x-data="{ openReqs: false }" class="bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-900/30 w-full mb-6 overflow-hidden transition-colors">
                <button @click="openReqs = !openReqs" class="w-full p-4 sm:p-5 flex items-center justify-between text-left focus:outline-none hover:bg-blue-100/50 dark:hover:bg-blue-900/20 transition-colors">
                    <h3 class="text-sm font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider flex items-center gap-2 m-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Required Documents for Librarian
                    </h3>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" :class="{'rotate-180': openReqs}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openReqs" style="display: none;">
                    <div class="px-5 pb-5 pt-1 border-t border-blue-100 dark:border-blue-900/30 mt-1">
                        <ul class="list-decimal list-inside text-sm text-blue-800 dark:text-blue-300 space-y-2 pl-2 font-medium mt-3">
                            <?php foreach ($librarian_requirements as $req): ?>
                                <li><?php echo htmlspecialchars($req); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-6">

            <!-- UPLOAD CARD -->
            <?php if ($isFullyCompleted): ?>
                <div class="bg-gray-50 dark:bg-warmdark-panel border border-green-200 dark:border-green-900/50 rounded-lg p-5 w-64 flex items-center justify-between opacity-90 transition-colors">
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
                            <input type="hidden" name="service_type" value="Librarian">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition">
                                Request Extra Round
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            <?php elseif ($canUploadNewRound): ?>
                <a href="student_dashboard.php?page=student_upload_librarian"
                    class="bg-white dark:bg-warmdark-panel shadow dark:shadow-md rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-transparent dark:border-warmdark-border">
                    <div>
                        <?php if ($max_phases > 1): ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Phase <?= $currentStatus === 'Approved' ? $currentPhase + 1 : $currentPhase ?></p>
                        <?php else: ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Upload</p>
                        <?php endif; ?>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            Upload Submission
                        </p>
                    </div>
                    <div class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl group-hover:scale-105 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                </a>

            <?php else: ?>
                <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-64 flex items-center justify-between opacity-75 transition-colors">
                    <div>
                        <?php if ($max_phases > 1): ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Phase <?= $currentPhase ?></p>
                        <?php else: ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Upload</p>
                        <?php endif; ?>
                        <p class="font-semibold text-gray-600 dark:text-gray-300">Pending Review</p>
                    </div>
                    <div class="text-yellow-600 dark:text-yellow-500 bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded-xl transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

            <!-- RESULT CARD -->
            <?php if ($isFullyCompleted): ?>
                <a href="student_dashboard.php?page=student_librarian_approved_result&id=<?php echo $latest['id']; ?>"
                    class="bg-white dark:bg-warmdark-panel shadow-sm border border-green-200 dark:border-green-900/50 border-l-4 border-l-green-500 dark:border-l-green-500 rounded-lg p-5 w-64 flex items-center justify-between hover:shadow-md transition group cursor-pointer">
                    <div>
                        <p class="text-xs text-green-600 dark:text-green-500 font-bold uppercase tracking-wider mb-0.5">Final Result</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">View Approved File</p>
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
                        <?php if ($max_phases > 1 && $currentPhase > 1): ?>
                            <p class="font-semibold text-gray-500 dark:text-gray-400 text-[13px]">Must complete Phase <?php echo $max_phases; ?></p>
                        <?php else: ?>
                            <p class="font-semibold text-gray-500 dark:text-gray-400">Result Not Available</p>
                        <?php endif; ?>
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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" title="<?= htmlspecialchars($application['assigned_role'] ?? 'Librarian') ?>">
                            <?= htmlspecialchars($application['assigned_role'] ?? 'Librarian') ?>
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
                History of All File Submit in Librarian
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
                        <?php if ($subs && $subs->num_rows > 0): ?>
                            <?php while ($row = $subs->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                                    <td class="py-3 text-xs font-semibold dark:text-gray-200">
                                        <?php if ($max_phases > 1): ?>
                                            Phase <?php echo (int)($row['phase'] ?? 1); ?>,
                                        <?php endif; ?>
                                        Round <?php echo (int)$row['round']; ?>
                                    </td>

                                    <td class="py-3 max-w-xs">
                                        <?php
                                        $fullName = basename($row['file_path']);
                                        ?>
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
                                                <a href="student_dashboard.php?page=student_view_librarian_report&id=<?php echo $row['id']; ?>"
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
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2-2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                        Locked
                                                    </span>
                                                <?php elseif ($canUpdateDoc): ?>
                                                    <!-- ACTIVE REUPLOAD BUTTON -->
                                                    <a href="student_dashboard.php?page=student_upload_librarian" class="flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm inline-flex">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Update
                                                    </a>
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
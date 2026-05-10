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
$reqStmt = $conn->prepare("SELECT required_phases, round_limit_per_phase FROM course_service_requirements WHERE course_id = ? AND service_type = 'Statistician'");
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
    WHERE sa.student_id = ? AND sa.service_type = 'Statistician' 
    ORDER BY sa.id DESC LIMIT 1
");
$appStmt->bind_param("i", $student_id);
$appStmt->execute();
$application = $appStmt->get_result()->fetch_assoc();
$appStmt->close();

$appStatus = $application ? $application['status'] : null;
// Add granted extra rounds to the max rounds limit
if ($application) {
    $max_rounds += (int)$application['extra_rounds'];
}

// --- DYNAMIC REASSIGNMENT LOGIC (FIXED) ---
$currentPersonnelName = $application['assigned_name'] ?? null;
$origPersonnelName = null;
$isReassigned = false;

if ($student_id && $currentPersonnelName) {
    // Check ONLY the logs to find if a formal reassignment occurred
    $logQuery = $conn->query("
        SELECT p1.full_name as original_name 
        FROM reassignment_logs r
        JOIN personnel p1 ON r.from_personnel_id = p1.id
        WHERE r.student_id = $student_id AND r.service_type = 'Statistician'
        ORDER BY r.reassigned_at ASC LIMIT 1
    ");
    if ($logQuery && $logQuery->num_rows > 0) {
        $logRes = $logQuery->fetch_assoc();
        $origPersonnelName = $logRes['original_name'];
        // Only trigger UI if the log shows a different person than current
        if ($origPersonnelName && $origPersonnelName !== $currentPersonnelName) {
            $isReassigned = true;
        }
    }
}

$hasAnySubmission = false;
if ($student_id) {
    $subCheck = $conn->query("SELECT id FROM statistician WHERE student_id = $student_id LIMIT 1");
    if ($subCheck && $subCheck->num_rows > 0) {
        $hasAnySubmission = true;
    }
}

// 3. Fetch Available Personnel for this Student's Department
$personnelList = [];
if (!$application || $appStatus === 'Rejected') {
    $pStmt = $conn->prepare("
        SELECT p.id, p.full_name 
        FROM personnel p 
        JOIN personnel_departments pd ON p.user_id = pd.user_id 
        WHERE p.service_role = 'Statistician' AND pd.department_id = ?
    ");
    $pStmt->bind_param("i", $student_dept_id);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    while ($row = $pRes->fetch_assoc()) {
        $personnelList[] = $row;
    }
    $pStmt->close();
}

// 4. If Application is Approved, load the actual file submissions
$subs = null;
$latest = null;
$currentRound = 0;
$currentPhase = 1;
$currentStatus = null;
$is_locked = 0;

if ($appStatus === 'Approved') {
    $subs = $conn->query("SELECT *, COALESCE(is_locked, 0) as is_locked FROM statistician WHERE student_id = $student_id ORDER BY phase DESC, round DESC, uploaded_at DESC");
    $latestRes = $conn->query("SELECT *, COALESCE(is_locked, 0) as is_locked FROM statistician WHERE student_id = $student_id ORDER BY phase DESC, round DESC LIMIT 1");
    $latest = $latestRes->fetch_assoc();
    $currentRound = $latest ? (int)$latest['round'] : 0;
    $currentPhase = $latest ? (int)($latest['phase'] ?? 1) : 1;
    $currentStatus = $latest ? $latest['status'] : null;
    $is_locked = $latest ? (int)$latest['is_locked'] : 0;
}

// 5. Fetch the specific requirements for Statistician
$req_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'req_desc_statistician_{$student_course_id}'");
$req_row = $req_stmt ? $req_stmt->fetch_assoc() : null;
$statistician_requirements_json = $req_row ? $req_row['setting_value'] : '[]';
$statistician_requirements = json_decode($statistician_requirements_json, true);

if (!is_array($statistician_requirements)) {
    $statistician_requirements = array_filter(explode("\n", $statistician_requirements_json));
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

    <?php if (!$application || $appStatus === 'Rejected'): ?>
        <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border max-w-2xl transition-colors">
            <div class="mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Request Statistician Services</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select your preferred personnel and upload your signed contract to begin.</p>

                <?php if ($appStatus === 'Rejected' && !$hasAnySubmission): ?>
                    <div x-data="{ show: true }"
                        x-show="show"
                        x-init="setTimeout(() => show = false, 5000)"
                        x-transition.opacity.duration.500ms
                        class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg text-sm border border-red-100 dark:border-red-900/30">
                        <span class="font-bold">Notice:</span> Your previous request was Rejected. Please submit a new request.
                    </div>
                <?php endif; ?>
            </div>

            <form action="../../backend/actions/apply_service_action.php" method="POST" enctype="multipart/form-data" class="space-y-6" onsubmit="showApplicationLoader()">
                <input type="hidden" name="service_type" value="Statistician">

                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Select Personnel <span class="text-red-500">*</span></label>
                    <select name="requested_personnel_id" required class="w-full border border-gray-300 dark:border-warmdark-border bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors appearance-none cursor-pointer">
                        <option value="">-- Choose an available Statistician --</option>
                        <?php foreach ($personnelList as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <!-- FIX: Updated label, added required attribute, expanded allowed formats -->
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Upload Contract <span class="text-red-500">*</span></label>
                    <input type="file" name="contract_file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-xl text-sm focus:outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400 cursor-pointer">
                    <p class="text-[11px] text-gray-400 mt-2 italic">Required. Accepted formats: PDF, DOC, DOCX, TXT, JPG, PNG. Max size: 5MB.</p>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-warmdark-border">
                    <button type="submit" id="submitApplicationBtn" class="w-full bg-blue-700 dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-md transition-colors flex justify-center items-center gap-2">
                        Submit Application Request
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($appStatus === 'Pending'): ?>
        <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl shadow-sm border border-yellow-200 dark:border-yellow-900/50 max-w-2xl transition-colors">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Application Pending</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Waiting for the System Administrator to verify your contract and officially assign your personnel.</p>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-100 dark:border-warmdark-border mt-6">
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Requested Personnel</p>
                <p class="text-gray-800 dark:text-gray-200 font-semibold"><?= htmlspecialchars($application['requested_name']) ?></p>
            </div>
        </div>

    <?php else: ?>

        <?php if (!$hasAnySubmission): ?>
            <div x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 5000)"
                x-transition.opacity.duration.500ms
                class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border border-green-200 dark:border-green-900/30 flex items-center gap-3 w-full max-w-4xl transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-green-800 dark:text-green-300 font-medium">Your Statistician application was approved! You may now submit your documents for review.</span>
            </div>
        <?php endif; ?>

        <!-- REQUIREMENTS DROPDOWN: Visible until ALL phases are completed -->
        <?php if (!empty($statistician_requirements) && $appStatus === 'Approved' && !$isFullyCompleted): ?>
            <div x-data="{ openReqs: false }" class="bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-900/30 w-full mb-6 overflow-hidden transition-colors">
                <button @click="openReqs = !openReqs" class="w-full p-4 sm:p-5 flex items-center justify-between text-left focus:outline-none hover:bg-blue-100/50 dark:hover:bg-blue-900/20 transition-colors">
                    <h3 class="text-sm font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider flex items-center gap-2 m-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Required Documents for Statistician
                    </h3>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" :class="{'rotate-180': openReqs}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openReqs" style="display: none;">
                    <div class="px-5 pb-5 pt-1 border-t border-blue-100 dark:border-blue-900/30 mt-1">
                        <ul class="list-decimal list-inside text-sm text-blue-800 dark:text-blue-300 space-y-2 pl-2 font-medium mt-3">
                            <?php foreach ($statistician_requirements as $req): ?>
                                <li><?php echo htmlspecialchars($req); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-5">
            <!-- UPLOAD CARD -->
            <?php if ($isFullyCompleted): ?>
                <div class="bg-gray-50 dark:bg-warmdark-panel border border-green-200 dark:border-green-900/50 rounded-lg p-5 w-full sm:w-64 flex items-center justify-between opacity-90 transition-colors">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <p class="font-semibold text-green-700 dark:text-green-500">Fully Completed</p>
                    </div>
                    <div class="text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-500/20 p-2 rounded-xl transition-colors shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

            <?php elseif ($needsRoundRequest): ?>
                <div class="bg-red-50 dark:bg-warmdark-panel shadow-sm border border-red-200 dark:border-red-900/50 rounded-lg p-5 w-full sm:w-64 flex flex-col items-center justify-center text-center transition-colors">
                    <p class="text-xs font-bold text-red-600 dark:text-red-500 uppercase tracking-wider mb-3">Max Rounds Reached</p>

                    <?php if ($application['round_request_status'] === 'Pending'): ?>
                        <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[11px] px-3 py-1.5 rounded-md font-bold border border-yellow-200 dark:border-yellow-800/50 w-full">
                            Request Pending...
                        </span>
                    <?php else: ?>
                        <form action="../../backend/actions/student/request_extra_round.php" method="POST" class="w-full">
                            <input type="hidden" name="service_type" value="Statistician">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition">
                                Request Extra Round
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($canUploadNewRound): ?>
                <a href="student_dashboard.php?page=student_upload_statistician"
                    class="bg-white dark:bg-warmdark-panel shadow dark:shadow-md rounded-lg p-5 w-full sm:w-64 flex items-center justify-between hover:shadow-md dark:hover:shadow-lg transition group border border-transparent dark:border-warmdark-border">
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
                    <div class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 p-2 rounded-xl group-hover:scale-105 transition-all shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                </a>

            <?php else: ?>
                <div class="bg-gray-50 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-full sm:w-64 flex items-center justify-between opacity-75 transition-colors">
                    <div>
                        <?php if ($max_phases > 1): ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Phase <?= $currentPhase ?></p>
                        <?php else: ?>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Upload</p>
                        <?php endif; ?>
                        <p class="font-semibold text-gray-600 dark:text-gray-300">Pending Review</p>
                    </div>
                    <div class="text-yellow-600 dark:text-yellow-500 bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded-xl transition-colors shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

            <!-- RESULT CARD -->
            <?php if ($isFullyCompleted): ?>
                <a href="student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $latest['id']; ?>"
                    class="bg-white dark:bg-warmdark-panel shadow-sm border border-green-200 dark:border-green-900/50 border-l-4 border-l-green-500 dark:border-l-green-500 rounded-lg p-5 w-full sm:w-64 flex items-center justify-between hover:shadow-md transition group cursor-pointer">
                    <div>
                        <p class="text-xs text-green-600 dark:text-green-500 font-bold uppercase tracking-wider mb-0.5">Final Result</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">View Approved File</p>
                    </div>
                    <div class="text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-500/10 p-2 rounded-full group-hover:bg-green-100 dark:group-hover:bg-green-500/20 transition-colors shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </a>
            <?php else: ?>
                <div class="bg-gray-100 dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-5 w-full sm:w-64 flex items-center justify-between opacity-70 transition-colors">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-0.5">Locked</p>
                        <?php if ($max_phases > 1 && $currentPhase > 1): ?>
                            <p class="font-semibold text-gray-500 dark:text-gray-400 text-[13px]">Must complete Phase <?php echo $max_phases; ?></p>
                        <?php else: ?>
                            <p class="font-semibold text-gray-500 dark:text-gray-400">Result Not Available</p>
                        <?php endif; ?>
                    </div>
                    <div class="text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-warmdark-bg p-2 rounded-full transition-colors shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PERSONNEL CARD -->
            <?php if ($application && $application['assigned_name']): ?>
                <div class="bg-white dark:bg-warmdark-panel shadow-sm border border-indigo-200 dark:border-indigo-900/50 border-l-4 border-l-indigo-500 dark:border-l-indigo-500 rounded-lg p-5 w-full sm:w-64 flex items-center justify-between transition-colors">
                    <div class="overflow-hidden pr-3 flex-1 min-w-0">

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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" title="<?= htmlspecialchars($application['assigned_role'] ?? 'Statistician') ?>">
                            <?= htmlspecialchars($application['assigned_role'] ?? 'Statistician') ?>
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
                History of All File Submit in Statistician
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-warmdark-bg text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Submission</th>
                            <th class="px-4 py-3 font-semibold">File</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Date & Time</th>
                            <th class="px-4 py-3 font-semibold text-center">Report</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                        <?php if ($subs && $subs->num_rows > 0): ?>
                            <?php while ($row = $subs->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                                    <td class="px-4 py-3 text-xs font-semibold dark:text-gray-200">
                                        <?php if ($max_phases > 1): ?>
                                            Phase <?php echo (int)($row['phase'] ?? 1); ?>,
                                        <?php endif; ?>
                                        Round <?php echo (int)$row['round']; ?>
                                    </td>
                                    <td class="px-4 py-3 max-w-xs">
                                        <?php $fullName = basename($row['file_path']); ?>
                                        <span class="block truncate text-gray-700 dark:text-gray-300" title="<?php echo htmlspecialchars($fullName); ?>">
                                            <?php echo htmlspecialchars($fullName); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
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

                                    <td class="px-4 py-3 text-xs whitespace-nowrap">
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

                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <?php if ($status === 'Approved' || $status === 'Needs Revision'): ?>
                                                <a href="student_dashboard.php?page=student_view_statistician_report&id=<?php echo $row['id']; ?>"
                                                    class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors shadow-sm font-bold inline-block">
                                                    View
                                                </a>
                                            <?php else: ?>
                                                <span class="bg-gray-100 dark:bg-warmdark-bg text-gray-400 dark:text-gray-600 border border-transparent px-3 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed inline-block">
                                                    View
                                                </span>
                                            <?php endif; ?>

                                            <?php
                                            $isRowLatest = ($row['id'] === $latest['id']);
                                            $rowLocked = (int)$row['is_locked'] === 1;
                                            ?>

                                            <?php if ($isRowLatest && $status === 'Pending'): ?>
                                                <?php if ($rowLocked): ?>
                                                    <span title="Personnel is currently reviewing this file" class="flex items-center gap-1.5 bg-gray-100 dark:bg-warmdark-bg text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-warmdark-border px-3 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed transition-colors shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                        Locked
                                                    </span>
                                                <?php elseif ($canUpdateDoc): ?>
                                                    <a href="student_dashboard.php?page=student_upload_statistician" class="flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm inline-flex">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Update
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
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
                                <td colspan="6" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
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

<div id="applicationLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Submitting Application...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please wait while we notify your personnel.</p>
    </div>
</div>

<script>
    window.showApplicationLoader = function() {
        document.getElementById('applicationLoadingOverlay').classList.remove('hidden');
        document.getElementById('applicationLoadingOverlay').classList.add('flex');

        const btn = document.getElementById('submitApplicationBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Processing...';
        }
    }
</script>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg'>Unauthorized access.</div>";
    exit();
}

$user_id = $_SESSION['user'];
$service_role_name = $_SESSION['service_role'] ?? '';
$is_global = ($service_role_name === 'Grammarly & AI Checking');

// 1. Map the service role to the DB service_type string
$serviceMap = [
    "Librarian" => "librarian",
    "Human Grammarian" => "human_grammarian",
    "Grammarly & AI Checking" => "grammarly_ai",
    "Statistician" => "statistician",
    "Ethics" => "ethics"
];
$service_type = $serviceMap[$service_role_name] ?? '';

if (empty($service_type)) {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg'>Invalid service role.</div>";
    exit();
}

// 2. Fetch the actual personnel_id using the user_id
$stmtP = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$stmtP->bind_param("i", $user_id);
$stmtP->execute();
$personnelResult = $stmtP->get_result()->fetch_assoc();
$stmtP->close();

if (!$personnelResult) {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg'>Personnel profile not found.</div>";
    exit();
}
$personnel_id = $personnelResult['id'];

// 3. Fetch Overall Average per Rubric (STRICTLY FILTERED BY CURRENT JUNCTION TABLE ASSIGNMENTS)
$rubricAverages = [];
$sqlRubrics = "
    SELECT r.id, r.name, COUNT(se.id) as total_evaluations, AVG(se.total_score) as average_total_score
    FROM rubrics r
    JOIN student_evaluations se ON r.id = se.rubric_id
    JOIN students s ON se.student_id = s.id
";
if (!$is_global) {
    $sqlRubrics .= " JOIN personnel_departments pd ON s.department_id = pd.department_id AND pd.user_id = ? ";
}
$sqlRubrics .= " WHERE se.personnel_id = ? AND se.service_type = ? GROUP BY r.id, r.name";

$stmtRubrics = $conn->prepare($sqlRubrics);
if (!$is_global) {
    $stmtRubrics->bind_param("iis", $user_id, $personnel_id, $service_type);
} else {
    $stmtRubrics->bind_param("is", $personnel_id, $service_type);
}
$stmtRubrics->execute();
$resRubrics = $stmtRubrics->get_result();
while ($row = $resRubrics->fetch_assoc()) {
    $rubricAverages[$row['id']] = [
        'name' => $row['name'],
        'total_evaluations' => $row['total_evaluations'],
        'average_total_score' => round($row['average_total_score'], 2),
        'criteria' => []
    ];
}
$stmtRubrics->close();

// 4. Fetch Average Score per Criterion (STRICTLY FILTERED)
$sqlCriteria = "
    SELECT rc.rubric_id, rc.name as criterion_name, AVG(ser.score) as average_score
    FROM rubric_criteria rc
    JOIN student_evaluation_ratings ser ON rc.id = ser.criterion_id
    JOIN student_evaluations se ON ser.evaluation_id = se.id
    JOIN students s ON se.student_id = s.id
";
if (!$is_global) {
    $sqlCriteria .= " JOIN personnel_departments pd ON s.department_id = pd.department_id AND pd.user_id = ? ";
}
$sqlCriteria .= " WHERE se.personnel_id = ? AND se.service_type = ? GROUP BY rc.rubric_id, rc.id, rc.name";

$stmtCriteria = $conn->prepare($sqlCriteria);
if (!$is_global) {
    $stmtCriteria->bind_param("iis", $user_id, $personnel_id, $service_type);
} else {
    $stmtCriteria->bind_param("is", $personnel_id, $service_type);
}
$stmtCriteria->execute();
$resCriteria = $stmtCriteria->get_result();
while ($row = $resCriteria->fetch_assoc()) {
    if (isset($rubricAverages[$row['rubric_id']])) {
        $rubricAverages[$row['rubric_id']]['criteria'][] = [
            'name' => $row['criterion_name'],
            'average_score' => round($row['average_score'], 1)
        ];
    }
}
$stmtCriteria->close();

// 5. Fetch Anonymous Comments (STRICTLY FILTERED)
$comments = [];
$sqlComments = "
    SELECT se.comments, se.created_at 
    FROM student_evaluations se
    JOIN students s ON se.student_id = s.id
";
if (!$is_global) {
    $sqlComments .= " JOIN personnel_departments pd ON s.department_id = pd.department_id AND pd.user_id = ? ";
}
$sqlComments .= " WHERE se.personnel_id = ? AND se.service_type = ? AND se.comments IS NOT NULL AND se.comments != '' ORDER BY se.created_at DESC";

$stmtComments = $conn->prepare($sqlComments);
if (!$is_global) {
    $stmtComments->bind_param("iis", $user_id, $personnel_id, $service_type);
} else {
    $stmtComments->bind_param("is", $personnel_id, $service_type);
}
$stmtComments->execute();
$resComments = $stmtComments->get_result();
while ($row = $resComments->fetch_assoc()) {
    $comments[] = $row;
}
$stmtComments->close();

?>

<div class="max-w-6xl mx-auto space-y-6 transition-colors duration-200">

    <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-colors">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Performance Ratings & Feedback</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View your aggregated evaluation scores and anonymous student feedback.</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/30 px-4 py-2 rounded-lg border border-blue-100 dark:border-blue-900/50 flex items-center gap-3 transition-colors">
            <div>
                <p class="text-[10px] uppercase font-bold text-blue-500 dark:text-blue-400 tracking-wider">Service Type</p>
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-300"><?php echo htmlspecialchars($service_role_name); ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($rubricAverages)): ?>
        <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-12 text-center flex flex-col items-center transition-colors">
            <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-full mb-4 border border-transparent dark:border-warmdark-border transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">No Evaluations Yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">You do not have any ratings or feedback from your currently assigned departments.</p>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($rubricAverages as $rubric): ?>
                <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border overflow-hidden flex flex-col transition-colors">

                    <div class="bg-gray-50 dark:bg-warmdark-bg border-b border-gray-100 dark:border-warmdark-border px-6 py-5 flex justify-between items-center relative z-20 transition-colors">

                        <div class="group flex items-center flex-1 min-w-0 pr-6 relative cursor-help">
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 truncate">
                                <?php echo htmlspecialchars($rubric['name']); ?>
                            </h2>

                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2 text-gray-400 dark:text-gray-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <div class="absolute left-0 top-full mt-3 w-max max-w-xs sm:max-w-sm md:max-w-md bg-gray-900 text-gray-100 text-xs font-medium px-4 py-3 rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 whitespace-normal leading-relaxed">
                                <?php echo htmlspecialchars($rubric['name']); ?>
                                <div class="absolute -top-1.5 left-5 w-3 h-3 bg-gray-900 transform rotate-45 rounded-sm"></div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Total Evals</p>
                            <p class="text-2xl font-black text-blue-600 dark:text-blue-400 leading-none"><?php echo $rubric['total_evaluations']; ?></p>
                        </div>
                    </div>


                    <div class="p-6 md:p-8 flex-1 flex flex-col bg-white dark:bg-warmdark-panel transition-colors">
                        <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Average Score Per Criterion</h4>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-400 to-indigo-500 shadow-sm"></span>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Max Score: 4.0</span>
                            </div>
                        </div>

                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-[40%] right-10 flex justify-between pointer-events-none z-0">
                                <div class="w-px h-full border-l border-dashed border-gray-200 dark:border-gray-700"></div>
                                <div class="w-px h-full border-l border-dashed border-gray-200 dark:border-gray-700"></div>
                                <div class="w-px h-full border-l border-dashed border-gray-200 dark:border-gray-700"></div>
                                <div class="w-px h-full border-l border-dashed border-gray-200 dark:border-gray-700"></div>
                                <div class="w-px h-full border-l border-dashed border-gray-200 dark:border-gray-700"></div>
                            </div>

                            <div class="space-y-6 relative z-10 pb-2">
                                <?php foreach ($rubric['criteria'] as $criterion): ?>
                                    <?php
                                    $score = floatval($criterion['average_score']);
                                    $percentage = min(100, ($score / 4) * 100);

                                    // Dynamic Gradient based on performance score
                                    if ($percentage >= 85) {
                                        $barColor = "from-emerald-400 to-green-500";
                                    } elseif ($percentage >= 60) {
                                        $barColor = "from-blue-400 to-indigo-500";
                                    } else {
                                        $barColor = "from-amber-400 to-orange-500";
                                    }
                                    ?>
                                    <div class="flex items-center group">
                                        <div class="w-2/5 pr-5">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 line-clamp-2 leading-snug group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors" title="<?php echo htmlspecialchars($criterion['name']); ?>">
                                                <?php echo htmlspecialchars($criterion['name']); ?>
                                            </span>
                                        </div>

                                        <div class="w-3/5 relative flex items-center">
                                            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-3.5 shadow-inner overflow-hidden mr-10 relative">
                                                <div class="h-full rounded-full bg-gradient-to-r <?php echo $barColor; ?> shadow-[0_0_10px_rgba(0,0,0,0.1)] transition-all duration-700 ease-out"
                                                    style="width: <?php echo $percentage; ?>%">
                                                </div>
                                            </div>
                                            <div class="absolute right-0 w-10 text-right">
                                                <span class="text-sm font-black text-gray-800 dark:text-gray-200"><?php echo number_format($score, 1); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex mt-2 pt-3 border-t border-gray-200 dark:border-warmdark-border relative z-10 bg-white dark:bg-warmdark-panel transition-colors">
                                <div class="w-2/5"></div>
                                <div class="w-3/5 relative pr-10">
                                    <div class="flex justify-between text-[11px] font-bold text-gray-400 dark:text-gray-500">
                                        <span class="w-4 text-center -ml-2">0</span>
                                        <span class="w-4 text-center">1</span>
                                        <span class="w-4 text-center">2</span>
                                        <span class="w-4 text-center">3</span>
                                        <span class="w-4 text-center -mr-2">4</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="bg-blue-50/50 dark:bg-blue-900/10 px-6 py-4 border-t border-gray-100 dark:border-warmdark-border flex justify-between items-center transition-colors">
                        <span class="font-bold text-gray-600 dark:text-gray-300 text-sm">Overall Average Score</span>
                        <span class="text-lg font-black text-blue-700 dark:text-blue-400"><?php echo $rubric['average_total_score']; ?></span>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-6 sm:p-8 mt-6 transition-colors">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6 pb-4 border-b border-gray-200 dark:border-warmdark-border flex items-center gap-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Student Feedback
            </h2>

            <?php if (empty($comments)): ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">No written feedback has been provided from your assigned departments yet.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-gray-50 dark:bg-warmdark-bg rounded-xl p-5 border border-gray-100 dark:border-warmdark-border hover:shadow-sm transition-all duration-200">
                            <div class="flex justify-between items-center mb-3">
                                <span class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-400 text-[10px] font-bold px-2 py-1 rounded shadow-sm uppercase tracking-wider transition-colors">Student Note</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium"><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($comment['comments']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>
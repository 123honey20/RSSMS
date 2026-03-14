<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg'>Unauthorized access.</div>";
    exit();
}

$user_id = $_SESSION['user'];
$service_role_name = $_SESSION['service_role'] ?? '';

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
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg'>Invalid service role.</div>";
    exit();
}

// 2. Fetch the actual personnel_id using the user_id
$stmtP = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$stmtP->bind_param("i", $user_id);
$stmtP->execute();
$personnelResult = $stmtP->get_result()->fetch_assoc();
$stmtP->close();

if (!$personnelResult) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg'>Personnel profile not found.</div>";
    exit();
}
$personnel_id = $personnelResult['id'];

// 3. Fetch Overall Average per Rubric
$rubricAverages = [];
$stmtRubrics = $conn->prepare("
    SELECT r.id, r.name, COUNT(se.id) as total_evaluations, AVG(se.total_score) as average_total_score
    FROM rubrics r
    JOIN student_evaluations se ON r.id = se.rubric_id
    WHERE se.personnel_id = ? AND se.service_type = ?
    GROUP BY r.id, r.name
");
$stmtRubrics->bind_param("is", $personnel_id, $service_type);
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

// 4. Fetch Average Score per Criterion
$stmtCriteria = $conn->prepare("
    SELECT rc.rubric_id, rc.name as criterion_name, AVG(ser.score) as average_score
    FROM rubric_criteria rc
    JOIN student_evaluation_ratings ser ON rc.id = ser.criterion_id
    JOIN student_evaluations se ON ser.evaluation_id = se.id
    WHERE se.personnel_id = ? AND se.service_type = ?
    GROUP BY rc.rubric_id, rc.id, rc.name
");
$stmtCriteria->bind_param("is", $personnel_id, $service_type);
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

// 5. Fetch Anonymous Comments
$comments = [];
$stmtComments = $conn->prepare("
    SELECT comments, created_at 
    FROM student_evaluations 
    WHERE personnel_id = ? AND service_type = ? AND comments IS NOT NULL AND comments != ''
    ORDER BY created_at DESC
");
$stmtComments->bind_param("is", $personnel_id, $service_type);
$stmtComments->execute();
$resComments = $stmtComments->get_result();
while ($row = $resComments->fetch_assoc()) {
    $comments[] = $row;
}
$stmtComments->close();

?>

<div class="max-w-6xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Performance Ratings & Feedback</h1>
            <p class="text-sm text-gray-500 mt-1">View your aggregated evaluation scores and anonymous student feedback.</p>
        </div>
        <div class="bg-blue-50 px-4 py-2 rounded-lg border border-blue-100 flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-md text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-blue-500 tracking-wider">Service Type</p>
                <p class="text-sm font-semibold text-blue-900"><?php echo htmlspecialchars($service_role_name); ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($rubricAverages)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center flex flex-col items-center">
            <div class="bg-gray-50 p-4 rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-700">No Evaluations Yet</h3>
            <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">You do not have any ratings or feedback from students at this time. Data will appear here once students submit their evaluations.</p>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($rubricAverages as $rubric): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                    
                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-5 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-800 truncate pr-4"><?php echo htmlspecialchars($rubric['name']); ?></h2>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Evals</p>
                            <p class="text-xl font-black text-blue-600 leading-none"><?php echo $rubric['total_evaluations']; ?></p>
                        </div>
                    </div>

                    <div class="p-6 flex-1">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Average Score Per Criterion</h4>
                        
                        <div class="space-y-4">
                            <?php foreach ($rubric['criteria'] as $criterion): ?>
                                <div>
                                    <div class="flex justify-between items-end mb-1">
                                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($criterion['name']); ?></span>
                                        <span class="text-sm font-black text-gray-900"><?php echo $criterion['average_score']; ?></span>
                                    </div>
                                    <?php $percentage = min(100, ($criterion['average_score'] / 4) * 100); ?>
                                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                                        <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-blue-50/50 px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-600 text-sm">Overall Average Score</span>
                        <span class="text-lg font-black text-blue-700"><?php echo $rubric['average_total_score']; ?></span>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                Anonymous Student Feedback
            </h2>

            <?php if (empty($comments)): ?>
                <div class="text-center py-8 text-gray-500 text-sm">No written feedback has been provided yet.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:shadow-sm transition">
                            <div class="flex justify-between items-center mb-3">
                                <span class="bg-white border border-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded shadow-sm uppercase tracking-wider">Student Note</span>
                                <span class="text-xs text-gray-400 font-medium"><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($comment['comments']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

// FIXED QUERY: Prioritize the historical personnel_id attached to this exact submission round!
// It falls back to the current service_applications assignment only if the round hasn't been processed yet.
$stmt = $conn->prepare("
    SELECT g.*, s.control_number, p.full_name AS personnel_name
    FROM grammarly_ai g
    JOIN students s ON g.student_id = s.id
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = 'Grammarly & AI Checking' AND sa.status = 'Approved'
    LEFT JOIN personnel p ON p.id = COALESCE(g.personnel_id, sa.assigned_personnel_id)
    WHERE g.id = ? AND s.user_id = ?
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/30 rounded-lg max-w-4xl mx-auto mt-8'>Submission not found or unauthorized access.</div>";
    exit();
}

// Original Student Submission Paths
$relativePath = "/RSSMS/uploads/grammarly_ai/submissions/" . $submission['file_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

// NEW: Processed Result Paths
$resultFile = $submission['result_file_path'] ?? null;
$resultRelativePath = $resultFile ? "/RSSMS/uploads/grammarly_ai_results/" . $resultFile : null;
$resultAbsolutePath = $resultFile ? $_SERVER['DOCUMENT_ROOT'] . $resultRelativePath : null;
$resultExists = $resultAbsolutePath && file_exists($resultAbsolutePath);

// Fetch Comments from personnel
$stmtComments = $conn->prepare("
    SELECT * FROM grammarly_ai_comments
    WHERE grammarly_ai_id = ?
    ORDER BY created_at ASC
");
$stmtComments->bind_param("i", $submissionId);
$stmtComments->execute();
$comments = $stmtComments->get_result();

$statusColor = "text-gray-600 dark:text-gray-400";
if ($submission['status'] === "Approved") $statusColor = "text-green-600 dark:text-green-400";
if ($submission['status'] === "Needs Revision") $statusColor = "text-red-600 dark:text-red-400";

?>
<div class="max-w-7xl mx-auto space-y-6 transition-colors duration-200">

    <div class="bg-white dark:bg-warmdark-panel rounded-lg shadow-sm border border-transparent dark:border-warmdark-border p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Grammarly & AI Checking Report Review</h2>
            <div class="flex flex-wrap items-center gap-4 mt-2 text-sm">
                <p class="text-gray-500 dark:text-gray-400">Control No: <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($submission['control_number']); ?></span></p>
                <p class="text-gray-500 dark:text-gray-400">Round: <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo $submission['round']; ?></span></p>
                <p class="text-gray-500 dark:text-gray-400">Status: <span class="font-semibold <?php echo $statusColor; ?>"><?php echo $submission['status']; ?></span></p>
                <p class="text-gray-500 dark:text-gray-400">Personnel: <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($submission['personnel_name'] ?? 'Pending Assignment'); ?></span></p>
            </div>
        </div>
        <div>
            <a href="student_dashboard.php?page=students_rs_grammarly_ai"
                class="bg-gray-800 dark:bg-gray-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-900 dark:hover:bg-gray-600 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
        </div>
    </div>

    <?php if ($resultExists): ?>
        <?php
            // Check if the file is previewable in an iframe
            $resultExt = strtolower(pathinfo($resultFile, PATHINFO_EXTENSION));
            $canPreviewResult = in_array($resultExt, ['pdf', 'jpg', 'jpeg', 'png', 'txt']);

            // Dynamic Styling based on Approved vs Needs Revision for BOTH Banner and Modal
            if ($submission['status'] === 'Needs Revision') {
                $bannerTheme = "bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50";
                $titleTheme = "text-red-800 dark:text-red-400";
                $descTheme = "text-red-700 dark:text-red-300";
                $btnSolidTheme = "bg-red-600 hover:bg-red-700 text-white";
                $btnOutlineTheme = "bg-white dark:bg-warmdark-panel border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30";
                $bannerTitle = "Feedback Document Ready";
                $bannerDesc = "Your assigned personnel has returned your document with feedback or required corrections.";
                
                // Modal specific theme mapping
                $modalHeaderBg = "bg-red-50 dark:bg-warmdark-bg";
                $modalIconBg = "bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400";
                $modalTitleText = "text-red-900 dark:text-red-400";
                $modalTitleString = "Feedback Document Viewer";
            } else {
                $bannerTheme = "bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-900/50";
                $titleTheme = "text-indigo-800 dark:text-indigo-400";
                $descTheme = "text-indigo-700 dark:text-indigo-300";
                $btnSolidTheme = "bg-indigo-600 hover:bg-indigo-700 text-white";
                $btnOutlineTheme = "bg-white dark:bg-warmdark-panel border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30";
                $bannerTitle = "Processed Result Ready";
                $bannerDesc = "Your assigned personnel has uploaded your finalized Grammarly & AI Checking result file.";
                
                // Modal specific theme mapping
                $modalHeaderBg = "bg-indigo-50 dark:bg-warmdark-bg";
                $modalIconBg = "bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400";
                $modalTitleText = "text-indigo-900 dark:text-indigo-400";
                $modalTitleString = "Processed Result Viewer";
            }
        ?>
        <div class="<?php echo $bannerTheme; ?> border rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-colors shadow-sm">
            <div>
                <h3 class="text-base font-bold <?php echo $titleTheme; ?> flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <?php echo $bannerTitle; ?>
                </h3>
                <p class="text-xs <?php echo $descTheme; ?> mt-0.5">
                    <?php echo $bannerDesc; ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php if ($canPreviewResult): ?>
                    <button onclick="openResultModal()" class="<?php echo $btnSolidTheme; ?> px-4 py-2 rounded-md text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Preview File
                    </button>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($resultRelativePath) ?>" download="<?= htmlspecialchars(basename($resultFile)) ?>" class="<?php echo $btnOutlineTheme; ?> border px-4 py-2 rounded-md text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download File
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white dark:bg-warmdark-panel rounded-lg shadow-sm border border-transparent dark:border-warmdark-border p-6 flex flex-col h-[775px] transition-colors">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100 dark:border-warmdark-border shrink-0 transition-colors">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Original Submitted Document</h3>
                <?php if (file_exists($absolutePath)): ?>
                    <button onclick="openFileModal()" class="text-sm bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 px-3 py-1.5 rounded-lg flex items-center gap-2 transition font-semibold border border-blue-100 dark:border-blue-900/50 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Expand File
                    </button>
                <?php endif; ?>
            </div>

            <?php if (file_exists($absolutePath)): ?>
                <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-warmdark-border shadow-sm flex-1 transition-colors">
                    <iframe src="<?php echo $relativePath; ?>" class="w-full h-full bg-gray-50 dark:bg-warmdark-bg transition-colors" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-lg flex items-center gap-2 border border-red-100 dark:border-red-900/30 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    File not found or has been moved.
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-warmdark-panel rounded-lg shadow-sm border border-transparent dark:border-warmdark-border p-6 flex flex-col h-[775px] transition-colors">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-6 pb-3 border-b border-gray-100 dark:border-warmdark-border transition-colors">Comments</h3>

            <div class="flex-1 overflow-y-auto space-y-5 pr-3 custom-scrollbar">
                <?php if ($comments->num_rows > 0): ?>
                    <?php $commentNum = 1; ?>
                    <?php while ($comment = $comments->fetch_assoc()): ?>
                        <div class="bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border p-5 rounded-2xl transition-colors hover:shadow-md group">

                            <div class="flex justify-between items-center mb-4">
                                <button
                                    onclick="openCommentModalDetails(this)"
                                    data-num="<?php echo $commentNum; ?>"
                                    data-date="<?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>"
                                    data-text="<?php echo htmlspecialchars($comment['comment_text']); ?>"
                                    class="text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/50 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 shadow-sm">
                                    Comment #<?php echo $commentNum++; ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>

                                <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                                    <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 leading-relaxed transition-colors">
                                <?php echo htmlspecialchars($comment['comment_text']); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-gray-400 dark:text-gray-500 mt-16 flex flex-col items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-200 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="font-medium text-gray-500 dark:text-gray-400">No Comments</p>
                        <p class="text-sm mt-1">The personnel has not added any comments to this submission.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div id="commentDetailModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-transparent dark:border-warmdark-border transition-colors">

        <div class="px-6 py-4 border-b border-gray-100 dark:border-warmdark-border flex justify-between items-center bg-gray-50 dark:bg-warmdark-bg transition-colors">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" id="modalCommentTitle">Comment Details</h3>
            <button onclick="closeCommentModalDetails()" class="text-gray-400 dark:text-gray-500 hover:text-white bg-gray-200 dark:bg-gray-700 hover:bg-red-500 p-1 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-6 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-warmdark-border pb-4 transition-colors">
                <div class="flex gap-5">
                    <span class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-bold mb-1">Date Posted</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-300" id="modalCommentDate">-</span>
                </div>
            </div>

            <div>
                <h4 class="text-[10px] uppercase tracking-wider text-blue-600 dark:text-blue-400 font-bold mb-3">Personnel Comment</h4>
                <div class="bg-blue-50/30 dark:bg-warmdark-bg p-4 rounded-xl border border-blue-50 dark:border-warmdark-border transition-colors">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed text-sm" id="modalCommentText"></p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 dark:border-warmdark-border bg-gray-50 dark:bg-warmdark-bg flex justify-end transition-colors">
            <button onclick="closeCommentModalDetails()" class="bg-gray-800 dark:bg-gray-700 hover:bg-gray-900 dark:hover:bg-gray-600 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow">
                Close
            </button>
        </div>
    </div>
</div>

<div id="fileDetailModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-md p-4 sm:p-6 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-7xl rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[95vh] border border-transparent dark:border-warmdark-border transition-colors">

        <div class="px-6 py-4 border-b border-gray-100 dark:border-warmdark-border flex justify-between items-center bg-gray-50 dark:bg-warmdark-bg shrink-0 transition-colors">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 p-1.5 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Original Document Viewer</h3>
            </div>
            <button onclick="closeFileModal()" class="text-gray-500 hover:text-white bg-gray-200 dark:bg-gray-700 hover:bg-red-500 p-1.5 rounded-full transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 w-full bg-gray-200 dark:bg-warmdark-panel p-2 sm:p-4 transition-colors">
            <iframe src="<?php echo $relativePath; ?>" class="w-full h-full rounded-xl border bg-white dark:bg-warmdark-bg border-gray-300 dark:border-warmdark-border shadow-sm transition-colors" frameborder="0"></iframe>
        </div>

    </div>
</div>

<?php if ($resultExists && isset($canPreviewResult) && $canPreviewResult): ?>
<div id="resultDetailModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-md p-4 sm:p-6 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-7xl rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[95vh] border border-transparent dark:border-warmdark-border transition-colors">

        <div class="px-6 py-4 border-b border-gray-100 dark:border-warmdark-border flex justify-between items-center <?php echo $modalHeaderBg; ?> shrink-0 transition-colors">
            <div class="flex items-center gap-3">
                <div class="<?php echo $modalIconBg; ?> p-1.5 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold <?php echo $modalTitleText; ?>"><?php echo $modalTitleString; ?></h3>
            </div>
            <button onclick="closeResultModal()" class="text-gray-500 hover:text-white bg-gray-200 dark:bg-gray-700 hover:bg-red-500 p-1.5 rounded-full transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 w-full bg-gray-200 dark:bg-warmdark-panel p-2 sm:p-4 transition-colors">
            <iframe src="<?php echo $resultRelativePath; ?>" class="w-full h-full rounded-xl border bg-white dark:bg-warmdark-bg border-gray-300 dark:border-warmdark-border shadow-sm transition-colors" frameborder="0"></iframe>
        </div>

    </div>
</div>
<?php endif; ?>

<script>
    // === COMMENT MODAL SCRIPTS ===
    function openCommentModalDetails(btn) {
        document.getElementById('modalCommentTitle').innerText = 'Comment #' + btn.getAttribute('data-num');
        document.getElementById('modalCommentDate').innerText = btn.getAttribute('data-date');
        document.getElementById('modalCommentText').innerText = btn.getAttribute('data-text');

        const modal = document.getElementById('commentDetailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCommentModalDetails() {
        const modal = document.getElementById('commentDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('commentDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCommentModalDetails();
        }
    });

    // === FILE MODAL SCRIPTS ===
    function openFileModal() {
        const modal = document.getElementById('fileDetailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeFileModal() {
        const modal = document.getElementById('fileDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('fileDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeFileModal();
        }
    });

    // === RESULT MODAL SCRIPTS ===
    function openResultModal() {
        const modal = document.getElementById('resultDetailModal');
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeResultModal() {
        const modal = document.getElementById('resultDetailModal');
        if(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    const resultModalEl = document.getElementById('resultDetailModal');
    if (resultModalEl) {
        resultModalEl.addEventListener('click', function(e) {
            if (e.target === this) {
                closeResultModal();
            }
        });
    }
</script>

<style>
    /* Custom Scrollbar specifically scoped for this page to override tailwind if necessary */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #475569;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>
<?php
session_start();
require_once "../../../backend/config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    echo "Unauthorized access!";
    exit();
}

$submissionId = intval($_GET['id']);
$viewOnly = isset($_GET['viewOnly']);

// FIXED QUERY: Added result_file_path so we can display it later
$stmt = $conn->prepare("
    SELECT t.id, t.file_path, t.status, s.control_number, t.result_file_path
    FROM statistician t
    JOIN students s ON t.student_id = s.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<p class='text-red-500 dark:text-red-400'>Submission not found.</p>";
    exit();
}

// Student's Original File Paths
$relativePath = "/RSSMS/uploads/statistician/" . $submission['file_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

// Personnel's Result File Paths
$resultFile = $submission['result_file_path'] ?? null;
$resultRelativePath = $resultFile ? "/RSSMS/uploads/statistician_results/" . $resultFile : null;
$resultAbsolutePath = $resultFile ? $_SERVER['DOCUMENT_ROOT'] . $resultRelativePath : null;
$resultExists = $resultAbsolutePath && file_exists($resultAbsolutePath);

$currentStatus = $submission['status'] ?? 'Pending';

// Get comment count
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total_comments
    FROM statistician_comments
    WHERE statistician_id = ?
");
$countStmt->bind_param("i", $submissionId);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalComments = $countResult['total_comments'] ?? 0;

?>
<div class="max-w-6xl mx-auto bg-white dark:bg-warmdark-panel rounded-2xl shadow-lg p-8 space-y-8 border border-transparent dark:border-warmdark-border transition-colors duration-200 relative">
    
    <div id="customToast" class="fixed bottom-5 right-5 transform translate-y-[150%] opacity-0 transition-all duration-300 bg-red-600 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-[100] border border-red-500/50">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <span id="toastMessage" class="text-sm font-semibold tracking-wide"></span>
    </div>

    <div class="flex justify-between items-start border-b border-gray-200 dark:border-warmdark-border pb-6 transition-colors">

        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                Submission Review
            </h2>
            <div class="mt-4 space-y-2 text-sm">
                <div>
                    <p class="text-gray-400 dark:text-gray-500 text-xs uppercase tracking-wide">
                        Control Number
                    </p>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($submission['control_number']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-end gap-3">
            <div class="text-right">
                <input type="hidden" id="initialCommentCount" value="<?= $totalComments ?>">
                <span id="commentHeaderCount" class="text-blue-700 dark:text-blue-400 text-xs font-bold">
                    <?php if (!$viewOnly): ?>You Currently Added<?php endif; ?> <?= $totalComments ?> Comment<?= $totalComments != 1 ? 's' : '' ?>
                </span>
            </div>

            <div class="text-xs font-bold px-4 py-1.5 rounded-full border transition-colors
                    <?php
                    if ($currentStatus == 'Approved') echo 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/20 border-green-200 dark:border-green-500/30';
                    elseif ($currentStatus == 'Rejected') echo 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-900/50';
                    else echo 'text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-900/50'; ?>">
                <?php echo $currentStatus; ?>
            </div>
        </div>

    </div>

    <div>
        <div class="flex justify-between items-center mb-3">
            <p class="text-gray-400 dark:text-gray-500 text-xs uppercase tracking-wide">
                Student Submission File
            </p>
            <?php if (file_exists($absolutePath)): ?>
                <a href="<?= htmlspecialchars($relativePath) ?>" download="<?= htmlspecialchars(basename($submission['file_path'])) ?>" class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-blue-100 dark:border-blue-900/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download Document
                </a>
            <?php endif; ?>
        </div>

        <?php if (file_exists($absolutePath)): ?>
            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-warmdark-border shadow-sm relative transition-colors">
                <iframe src="<?= htmlspecialchars($relativePath) ?>"
                    class="w-full h-[650px] bg-gray-50 dark:bg-warmdark-bg relative z-10 transition-colors"
                    frameborder="0">
                </iframe>
            </div>
        <?php else: ?>
            <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-lg font-medium border border-red-100 dark:border-red-900/30 transition-colors">
                File not found.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($currentStatus !== 'Pending'): ?>
        <div class="mt-8 border-t border-gray-200 dark:border-warmdark-border pt-8">
            <div class="flex justify-between items-center mb-3">
                <p class="text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-wide">
                    Your Uploaded Result/Feedback File
                </p>
                <?php if ($resultExists): ?>
                    <a href="<?= htmlspecialchars($resultRelativePath) ?>" download="<?= htmlspecialchars(basename($submission['result_file_path'])) ?>" class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-blue-100 dark:border-blue-900/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download Result
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($resultExists): ?>
                <div class="rounded-xl overflow-hidden border border-blue-200 dark:border-blue-900/30 shadow-sm relative transition-colors">
                    <iframe src="<?= htmlspecialchars($resultRelativePath) ?>"
                        class="w-full h-[650px] bg-blue-50/30 dark:bg-warmdark-bg relative z-10 transition-colors"
                        frameborder="0">
                    </iframe>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 dark:bg-warmdark-bg text-gray-500 dark:text-gray-400 p-4 rounded-lg font-medium border border-gray-200 dark:border-warmdark-border transition-colors">
                    No result file was attached during processing.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!$viewOnly && $currentStatus === 'Pending'): ?>
        
        <div x-data="{ requireFile: true }" class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-900/30 rounded-xl p-6 transition-colors">
            
            <div class="flex items-center justify-between mb-4 border-b border-blue-200/50 dark:border-blue-900/30 pb-4">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-400">Process Student Submission</h3>
                
                <label class="relative inline-flex items-center cursor-pointer" title="Toggle File Upload Requirement">
                    <input type="checkbox" x-model="requireFile" @change="if(!requireFile) document.getElementById('resultFileInput').value = '';" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <form action="../../backend/actions/personnel_process_statistician.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                
                <div x-show="requireFile" x-collapse x-cloak>
                    <div class="bg-white dark:bg-warmdark-bg p-4 rounded-lg border border-gray-200 dark:border-warmdark-border">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Attach Result/Feedback File</label>
                        <input type="file" id="resultFileInput" name="result_file" accept=".docx,.pdf,.txt,.csv,.xlsx,.sav,.png,.jpg" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-xl text-sm focus:outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400">
                        <p class="text-[11px] text-gray-400 mt-2 italic">Allowed: .docx, .pdf, .txt, .csv, .xlsx, .sav, .png, .jpg</p>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3" x-text="requireFile ? 'Finalize Decision' : 'Finalize Decision (No File Attached)'"></label>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" name="action" value="Approve" @click="if(requireFile && !document.getElementById('resultFileInput').value) { $event.preventDefault(); showToast('Please attach a result/feedback file before clicking Approve.', 'error'); }" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg text-sm font-bold shadow-md transition-colors w-full sm:w-auto">
                            Approve Submission
                        </button>
                        <button type="submit" name="action" value="Reject" @click="if(requireFile && !document.getElementById('resultFileInput').value) { $event.preventDefault(); showToast('Please attach a result/feedback file before clicking Reject.', 'error'); }" class="bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 px-8 py-2.5 rounded-lg text-sm font-bold transition-colors w-full sm:w-auto">
                            Reject Submission
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="flex justify-between pt-6 border-t border-gray-200 dark:border-warmdark-border transition-colors">
        <div>
            <button
                id="viewCommentBtn"
                onclick="openViewCommentModal(<?= $submission['id'] ?>)"
                class="px-6 py-2.5 rounded-lg text-sm font-medium shadow transition bg-blue-600 dark:bg-blue-700 text-white hover:bg-blue-700 dark:hover:bg-blue-600 <?= $totalComments == 0 ? 'hidden' : '' ?>">
                View Comment
            </button>
        </div>
        <div class="flex items-center gap-3">
            <button
                onclick="location.reload()"
                class="bg-gray-200 dark:bg-warmdark-bg text-gray-800 dark:text-gray-200 px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-300 dark:hover:bg-warmdark-border transition">
                Back
            </button>
            <?php if (!$viewOnly && $currentStatus === 'Pending'): ?>
                <button
                    onclick="openCommentModal(<?= $submission['id'] ?>)"
                    class="bg-gray-800 dark:bg-gray-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-900 dark:hover:bg-gray-600 transition">
                    Add Comment
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    let toastTimeout;
    function showToast(message) {
        const toast = document.getElementById('customToast');
        document.getElementById('toastMessage').textContent = message;
        
        clearTimeout(toastTimeout);
        
        // Show
        toast.classList.remove('translate-y-[150%]', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        
        // Hide after 3.5 seconds
        toastTimeout = setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-[150%]', 'opacity-0');
        }, 3500);
    }
</script>

<div id="commentModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[500px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4 border border-transparent dark:border-warmdark-border transition-colors">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Add Comment</h3>
        <div id="commentError" class="hidden text-red-600 dark:text-red-400 text-sm mt-2">Please complete all fields.</div>
        <div id="commentCounter" class="text-sm text-gray-500 dark:text-gray-400">Comment No. <span><?= $totalComments ?></span></div>
        <div>
            <label class="text-sm text-gray-600 dark:text-gray-400">Page Number</label>
            <input type="number" id="commentPage" class="w-full mt-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
        </div>
        <div>
            <label class="text-sm text-gray-600 dark:text-gray-400">Paragraph Number</label>
            <input type="number" id="commentParagraph" class="w-full mt-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
        </div>
        <div>
            <label class="text-sm text-gray-600 dark:text-gray-400">Comment</label>
            <textarea id="commentText" class="w-full mt-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors" rows="4"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-warmdark-border mt-2 transition-colors">
            <button onclick="closeCommentModal()" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:underline transition-colors">Cancel</button>
            <button id="saveCommentBtn" class="bg-blue-600 dark:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">Done</button>
        </div>
    </div>
</div>

<div id="viewCommentModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[600px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4 border border-transparent dark:border-warmdark-border transition-colors">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Submitted Comments</h3>
        <div id="viewCommentList" class="max-h-[400px] overflow-y-auto space-y-3 text-sm custom-scrollbar pr-2">
        </div>
        <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-warmdark-border transition-colors">
            <button onclick="closeViewCommentModal()" class="bg-gray-800 dark:bg-gray-700 text-white px-5 py-2 rounded-lg text-sm hover:bg-gray-900 dark:hover:bg-gray-600 transition-colors">Close</button>
        </div>
    </div>
</div>
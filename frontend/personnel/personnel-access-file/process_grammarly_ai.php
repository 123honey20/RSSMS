<?php
session_start();
require_once "../../../backend/config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    echo "Unauthorized access!";
    exit();
}

$submissionId = intval($_GET['id']);
$viewOnly = isset($_GET['viewOnly']);

// LOCK THE DOCUMENT IF PERSONNEL IS PROCESSING IT
if (!$viewOnly) {
    $lockStmt = $conn->prepare("UPDATE grammarly_ai SET is_locked = 1 WHERE id = ?");
    $lockStmt->bind_param("i", $submissionId);
    $lockStmt->execute();
    $lockStmt->close();
}

// Fetch BOTH Document and Receipt info in one query!
$stmt = $conn->prepare("
    SELECT g.id, g.file_path, g.status, g.round, g.phase, g.student_id, g.is_locked,
           s.control_number, g.result_file_path, 
           p.full_name as assigned_personnel,
           t.receipt_path, t.status as transaction_status
    FROM grammarly_ai g
    JOIN students s ON g.student_id = s.id
    LEFT JOIN grammarly_ai_transactions t ON g.student_id = t.student_id AND g.phase = t.phase AND g.round = t.round
    LEFT JOIN service_applications sa ON sa.student_id = s.id AND sa.service_type = 'Grammarly & AI Checking' AND sa.status = 'Approved'
    LEFT JOIN personnel p ON sa.assigned_personnel_id = p.id
    WHERE g.id = ?
");
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-8'><p class='text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-900/50'>Submission not found.</p></div>";
    exit();
}

// 1. Document Paths
$docRelativePath = "/RSSMS/uploads/grammarly_ai/submissions/" . $submission['file_path'];
$docAbsolutePath = $_SERVER['DOCUMENT_ROOT'] . $docRelativePath;

// 2. Receipt Paths
$receiptFile = $submission['receipt_path'] ?? null;
$receiptRelativePath = $receiptFile ? "/RSSMS/uploads/grammarly_ai/receipts/" . $receiptFile : null;
$receiptAbsolutePath = $receiptFile ? $_SERVER['DOCUMENT_ROOT'] . $receiptRelativePath : null;
$receiptExists = $receiptAbsolutePath && file_exists($receiptAbsolutePath);

// 3. Result Paths
$resultFile = $submission['result_file_path'] ?? null;
$resultRelativePath = $resultFile ? "/RSSMS/uploads/grammarly_ai_results/" . $resultFile : null;
$resultAbsolutePath = $resultFile ? $_SERVER['DOCUMENT_ROOT'] . $resultRelativePath : null;
$resultExists = $resultAbsolutePath && file_exists($resultAbsolutePath);

$currentStatus = $submission['status'] ?? 'Pending';
$transactionStatus = $submission['transaction_status'] ?? 'Pending';

// Smart Tab Logic (Open Receipt tab first if it needs review)
$defaultTab = ($transactionStatus !== 'Approved') ? 'receipt' : 'document';

// FETCH MAX PHASES FOR THIS SPECIFIC COURSE
$maxPhaseStmt = $conn->prepare("SELECT required_phases FROM course_service_requirements WHERE course_id = (SELECT course_id FROM students WHERE id = ?) AND service_type = 'Grammarly & AI Checking'");
$maxPhaseStmt->bind_param("i", $submission['student_id']);
$maxPhaseStmt->execute();
$maxPhaseRes = $maxPhaseStmt->get_result()->fetch_assoc();
$max_phases = $maxPhaseRes ? (int)$maxPhaseRes['required_phases'] : 1;
$maxPhaseStmt->close();

$currentPhase = isset($submission['phase']) ? (int)$submission['phase'] : 1;
$phaseStr = ($max_phases > 1) ? "Phase {$currentPhase}, " : "";

// Comment Count
$countStmt = $conn->prepare("SELECT COUNT(*) as total_comments FROM grammarly_ai_comments WHERE grammarly_ai_id = ?");
$countStmt->bind_param("i", $submissionId);
$countStmt->execute();
$totalComments = $countStmt->get_result()->fetch_assoc()['total_comments'] ?? 0;
?>

<div class="max-w-6xl mx-auto bg-white dark:bg-warmdark-panel rounded-2xl shadow-lg border border-transparent dark:border-warmdark-border transition-colors duration-200" x-data="{ tab: '<?= $defaultTab ?>' }">
    
    <div class="p-8 border-b border-gray-200 dark:border-warmdark-border flex flex-wrap justify-between items-center gap-4 transition-colors">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Submission Review</h2>
            <div class="mt-2 flex items-center gap-4 text-sm">
                <span class="text-gray-500 dark:text-gray-400">Control No: <strong class="text-gray-800 dark:text-gray-200"><?= htmlspecialchars($submission['control_number']) ?></strong></span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <span class="text-gray-500 dark:text-gray-400"><?= $phaseStr ?>Round: <strong class="text-gray-800 dark:text-gray-200"><?= $submission['round'] ?></strong></span>
            </div>
        </div>

        <div class="flex flex-col items-end gap-2">
            <span class="px-4 py-1.5 rounded-full text-xs font-bold border tracking-wide uppercase shadow-sm
                <?php
                if ($currentStatus == 'Approved') echo 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/20 border-green-200 dark:border-green-500/30';
                elseif ($currentStatus == 'Needs Revision') echo 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-900/50';
                else echo 'text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-900/50'; ?>">
                <?= $currentStatus ?>
            </span>
            <input type="hidden" id="initialCommentCount" value="<?= $totalComments ?>">
            <span id="commentHeaderCount" class="text-blue-600 dark:text-blue-400 text-xs font-bold">
                <?= $totalComments ?> Comment<?= $totalComments != 1 ? 's' : '' ?> Added
            </span>
        </div>
    </div>

    <div class="px-8 pt-4 border-b border-gray-200 dark:border-warmdark-border flex gap-6 overflow-x-auto custom-scrollbar">
        <button @click="tab = 'document'" :class="tab === 'document' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="pb-3 border-b-2 text-sm transition-colors whitespace-nowrap flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Research Document
        </button>
        <button @click="tab = 'receipt'" :class="tab === 'receipt' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="pb-3 border-b-2 text-sm transition-colors whitespace-nowrap flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            Payment Receipt
        </button>
        <?php if ($resultExists): ?>
        <button @click="tab = 'result'" :class="tab === 'result' ? 'border-green-600 text-green-600 dark:border-green-400 dark:text-green-400 font-bold' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" class="pb-3 border-b-2 text-sm transition-colors whitespace-nowrap flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            Personnel Result
        </button>
        <?php endif; ?>
    </div>

    <div x-show="tab === 'document'" class="p-8" x-cloak>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Submitted Document</h3>
            <?php if ($transactionStatus === 'Approved' && file_exists($docAbsolutePath)): ?>
                <a href="<?= htmlspecialchars($docRelativePath) ?>" download class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                    Download File
                </a>
            <?php endif; ?>
        </div>

        <?php if ($transactionStatus !== 'Approved'): ?>
            <div class="bg-amber-50 dark:bg-yellow-900/20 text-amber-700 dark:text-yellow-500 p-10 rounded-xl border border-amber-200 dark:border-yellow-900/30 text-center font-medium flex flex-col items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 mb-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <span class="text-lg font-bold">Document Locked</span>
                <span class="text-sm text-amber-600 dark:text-yellow-600 mt-2 max-w-md">Please verify and approve the payment receipt in the <strong>Payment Receipt</strong> tab to unlock and review the research document.</span>
            </div>
        <?php elseif (file_exists($docAbsolutePath)): ?>
            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-warmdark-border shadow-sm">
                <iframe src="<?= htmlspecialchars($docRelativePath) ?>" class="w-full h-[650px] bg-gray-50 dark:bg-warmdark-bg" frameborder="0"></iframe>
            </div>
        <?php else: ?>
            <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-xl border border-red-100 dark:border-red-900/30 text-center font-medium">Document file not found on server.</div>
        <?php endif; ?>
    </div>

    <div x-show="tab === 'receipt'" class="p-8" x-cloak>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Payment Receipt</h3>
            <?php if ($receiptExists): ?>
                <a href="<?= htmlspecialchars($receiptRelativePath) ?>" download class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                    Download Receipt
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($transactionStatus === 'Approved'): ?>
            <div class="mb-5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/30 p-3.5 rounded-xl flex items-center gap-3 text-green-700 dark:text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span class="text-sm font-medium">This payment receipt has already been verified and <strong>Approved</strong>.</span>
            </div>
        <?php elseif ($transactionStatus === 'Needs Revision'): ?>
            <div class="mb-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/30 p-3.5 rounded-xl flex items-center gap-3 text-red-700 dark:text-red-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                <span class="text-sm font-medium">This payment receipt was <strong>(Needs Revision)</strong>. Waiting for the student to re-upload.</span>
            </div>
        <?php endif; ?>

        <?php if ($receiptExists): ?>
            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-warmdark-border shadow-sm bg-gray-50 dark:bg-warmdark-bg flex justify-center h-[650px]">
                <?php 
                $ext = strtolower(pathinfo($receiptFile, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="<?= htmlspecialchars($receiptRelativePath) ?>" class="max-h-full object-contain p-4">
                <?php else: ?>
                    <iframe src="<?= htmlspecialchars($receiptRelativePath) ?>" class="w-full h-full" frameborder="0"></iframe>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 p-8 rounded-xl border border-yellow-200 dark:border-yellow-900/30 text-center font-medium flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                No receipt was uploaded for this round.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($resultExists): ?>
    <div x-show="tab === 'result'" class="p-8" x-cloak>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-bold text-green-700 dark:text-green-500 uppercase tracking-wider">Your Processed Result</h3>
            <a href="<?= htmlspecialchars($resultRelativePath) ?>" download class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-green-100 dark:hover:bg-green-900/50 transition border border-green-200 dark:border-green-900/50">
                Download Result
            </a>
        </div>
        <div class="rounded-xl overflow-hidden border border-green-200 dark:border-green-900/30 shadow-sm">
            <iframe src="<?= htmlspecialchars($resultRelativePath) ?>" class="w-full h-[650px] bg-gray-50 dark:bg-warmdark-bg" frameborder="0"></iframe>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$viewOnly): ?>
        
        <?php if (in_array($transactionStatus, ['Receipt Uploaded', 'Pending'])): ?>
            <div class="p-8 bg-gray-50/50 dark:bg-warmdark-bg border-t border-gray-200 dark:border-warmdark-border">
                <h3 class="text-lg font-bold text-amber-700 dark:text-amber-500 mb-2">Step 1: Payment Receipt Verification</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">You must approve the payment receipt before reviewing the research document.</p>
                
                <form action="../../backend/actions/personnel_process_grammarly_ai.php" method="POST" class="flex flex-wrap gap-3" onsubmit="window.showProcessLoader()">
                    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                    <input type="hidden" name="action" id="receipt_action_input" value="">
                    
                    <button type="submit" onclick="document.getElementById('receipt_action_input').value='Approve Receipt'" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-md transition-colors flex-1 sm:flex-none">
                        Approve Receipt
                    </button>
                    <button type="submit" onclick="document.getElementById('receipt_action_input').value='Reject Receipt'" class="bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 px-8 py-3 rounded-xl text-sm font-bold transition-colors flex-1 sm:flex-none">
                        Reject Receipt
                    </button>
                </form>
            </div>

        <?php elseif ($transactionStatus === 'Needs Revision'): ?>
            <div class="p-8 bg-red-50/50 dark:bg-red-900/10 border-t border-red-200 dark:border-red-900/30">
                <h3 class="text-lg font-bold text-red-700 dark:text-red-400 mb-2">Step 1: Waiting for Student</h3>
                <p class="text-sm text-red-600 dark:text-red-300 font-medium">You rejected the payment receipt. Please wait for the student to upload a new one before proceeding.</p>
            </div>

        <?php elseif ($transactionStatus === 'Approved' && $currentStatus === 'Pending'): ?>
            <div class="p-8 bg-gray-50/50 dark:bg-warmdark-bg border-t border-gray-200 dark:border-warmdark-border" x-data="{ requireFile: true }">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-400 mb-2">Step 2: Process Submission</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">The receipt is approved. You can now review the document and upload your result.</p>
                
                <form action="../../backend/actions/personnel_process_grammarly_ai.php" method="POST" enctype="multipart/form-data" class="space-y-5" onsubmit="window.showProcessLoader()">
                    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                    <input type="hidden" name="action" id="hidden_action_input" value="">
                    
                    <div class="bg-white dark:bg-warmdark-panel p-5 rounded-xl border border-gray-200 dark:border-warmdark-border shadow-sm">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Attach Result File (Required)</label>
                        <input type="file" id="resultFileInput" name="result_file" accept=".docx,.pdf,.txt,.csv,.xlsx,.sav,.png,.jpg" class="w-full border border-gray-300 dark:border-warmdark-border bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 px-4 py-2.5 rounded-xl text-sm focus:outline-none transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-100 dark:file:bg-blue-900/40 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-200 cursor-pointer">
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" @click="if(!document.getElementById('resultFileInput').value) { $event.preventDefault(); showToast('Please attach a result file.', 'error'); } else { document.getElementById('hidden_action_input').value = 'Approve'; }" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-md transition-colors flex-1 sm:flex-none">
                            Approve Document
                        </button>
                        
                        <button type="submit" @click="if(!document.getElementById('resultFileInput').value) { $event.preventDefault(); showToast('Please attach a result file.', 'error'); } else { document.getElementById('hidden_action_input').value = 'Needs Revision'; }" class="bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 px-8 py-3 rounded-xl text-sm font-bold transition-colors flex-1 sm:flex-none">
                            Needs Revision
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="p-6 border-t border-gray-200 dark:border-warmdark-border flex justify-between items-center bg-white dark:bg-warmdark-panel rounded-b-2xl">
        <button id="viewCommentBtn" onclick="openViewCommentModal(<?= $submission['id'] ?>)" class="px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-800/50 <?= $totalComments == 0 ? 'hidden' : '' ?>">
            View Comments
        </button>
        
        <div class="flex gap-3 ml-auto">
            <button onclick="location.reload()" class="bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-warmdark-border px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-200 dark:hover:bg-warmdark-hover transition-colors">
                Back
            </button>
            <?php if (!$viewOnly && $currentStatus === 'Pending' && $transactionStatus === 'Approved'): ?>
                <button onclick="openCommentModal(<?= $submission['id'] ?>)" class="bg-gray-800 dark:bg-gray-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-gray-900 dark:hover:bg-gray-600 transition-colors">
                    Add Comment
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="customToast" class="fixed bottom-5 right-5 transform translate-y-[150%] opacity-0 transition-all duration-300 bg-red-600 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-[100]">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    <span id="toastMessage" class="text-sm font-bold tracking-wide"></span>
</div>

<div id="commentModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-[500px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4 border border-transparent dark:border-warmdark-border transition-colors">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Add Comment</h3>
        <div id="commentError" class="hidden text-red-600 dark:text-red-400 text-sm mt-2">Please complete all fields.</div>
        <div id="commentCounter" class="text-sm text-gray-500 dark:text-gray-400">Comment No. <span><?= $totalComments + 1 ?></span></div>
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
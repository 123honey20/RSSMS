<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

// SMART ROUND & RECEIPT LOGIC
$sub = $conn->query("SELECT round, status, is_locked FROM grammarly_ai WHERE student_id = $student_id ORDER BY round DESC LIMIT 1");
$existing = $sub->fetch_assoc();

$receiptOnlyMode = false;
$nextRound = 1;
$is_locked = 0;

if ($existing) {
    $is_locked = (int)($existing['is_locked'] ?? 0);
    $transCheck = $conn->query("SELECT status FROM grammarly_ai_transactions WHERE student_id = $student_id AND round = {$existing['round']}");
    $trans = $transCheck->fetch_assoc();

    if ($existing['status'] === 'Needs Revision') {
        $nextRound = $existing['round'] + 1; 
    } elseif ($existing['status'] === 'Pending' && $trans && $trans['status'] === 'Needs Revision') {
        $receiptOnlyMode = true; 
        $nextRound = $existing['round'];
    } else {
        $nextRound = $existing['round'];
    }
}

// HANDLE MANUAL RE-UPLOAD MODES FROM MODAL
$urlMode = $_GET['mode'] ?? '';
$showReceipt = true;
$showDocument = true;

// Security check: Block manually forced document upload if locked!
if ($urlMode === 'document' && $is_locked === 1) {
    $_SESSION['flash_error'] = "The personnel is currently reviewing your document. You cannot re-upload it.";
    echo "<script>window.location.href='student_dashboard.php?page=students_rs_grammarly_ai';</script>";
    exit();
}

if ($urlMode === 'receipt' || $receiptOnlyMode) {
    $showDocument = false;
    $submitText = 'Re-upload Receipt';
} elseif ($urlMode === 'document') {
    $showReceipt = false;
    $submitText = 'Re-upload Document';
} else {
    $submitText = 'Submit Both Files';
}

$activeMode = $urlMode ?: ($receiptOnlyMode ? 'receipt' : 'both');
?>

<div class="max-w-4xl mx-auto py-10 px-4 w-full transition-colors duration-200">

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="toast-error" class="fixed top-6 right-6 bg-red-600 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0 font-medium text-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span><?php echo $_SESSION['flash_error']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-error')?.classList.add('opacity-0', 'translate-x-full'); }, 4000);</script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight">
                <?= $submitText ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Round <?php echo $nextRound; ?> • Grammarly & AI Checking</p>
        </div>
        <a href="student_dashboard.php?page=students_rs_grammarly_ai"
            class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-700 dark:text-gray-300 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
        </a>
    </div>

    <!-- NOTIFICATION BANNER IF RECEIPT WAS REJECTED -->
    <?php if ($receiptOnlyMode && !$urlMode): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Action Required: Receipt Rejected</h3>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">Your previous payment receipt was rejected by the checking personnel. Please upload a clear, valid receipt below to proceed with your document review.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODERN UPLOAD FORM -->
    <div class="bg-white dark:bg-warmdark-panel rounded-3xl shadow-sm border border-gray-100 dark:border-warmdark-border p-8 transition-colors">
        <form action="../../backend/actions/upload-submission/upload_grammarly_ai.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
            
            <input type="hidden" name="update_mode" value="<?= htmlspecialchars($activeMode) ?>">
            <input type="hidden" name="round" value="<?= $nextRound ?>">

            <div class="grid <?= (!$showReceipt || !$showDocument) ? 'grid-cols-1 max-w-xl mx-auto' : 'grid-cols-1 md:grid-cols-2 gap-8' ?> mb-10">
                
                <!-- 1. RECEIPT UPLOAD -->
                <?php if ($showReceipt): ?>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 font-bold text-xs">1</span>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Payment Receipt</h3>
                    </div>
                    
                    <label for="receipt-file" id="receipt-dropzone" class="flex-1 flex flex-col items-center justify-center w-full min-h-[200px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl cursor-pointer bg-gray-50/50 dark:bg-warmdark-bg hover:bg-blue-50/50 dark:hover:bg-blue-900/10 hover:border-blue-400 transition-all group relative overflow-hidden">
                        
                        <div id="receipt-default" class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                            <div class="w-12 h-12 bg-white dark:bg-warmdark-panel shadow-sm rounded-full flex items-center justify-center mb-3 group-hover:scale-110 group-hover:text-blue-500 transition-transform text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Click to upload receipt</p>
                            <p class="text-[11px] text-gray-400 mt-1 uppercase tracking-wide">JPG, PNG, PDF, DOCX</p>
                        </div>

                        <div id="receipt-selected" class="hidden flex-col items-center justify-center w-full h-full bg-blue-50/80 dark:bg-blue-900/20 absolute inset-0 text-center px-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center mb-2 text-blue-600 dark:text-blue-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span id="receipt-name" class="text-sm font-bold text-blue-900 dark:text-blue-200 truncate w-full px-2"></span>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 hover:underline">Click to change</p>
                        </div>

                        <input id="receipt-file" type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="hidden" onchange="handleFileSelect(this, 'receipt-default', 'receipt-selected', 'receipt-name', 'receipt-dropzone', 'receipt-error')" />
                    </label>
                    <p id="receipt-error" class="hidden text-red-500 dark:text-red-400 text-xs font-bold mt-2 text-center">Receipt is required.</p>
                </div>
                <?php endif; ?>

                <!-- 2. DOCUMENT UPLOAD -->
                <?php if ($showDocument): ?>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 font-bold text-xs"><?= $showReceipt ? '2' : '1' ?></span>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Research Document</h3>
                    </div>

                    <label for="document-file" id="document-dropzone" class="flex-1 flex flex-col items-center justify-center w-full min-h-[200px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl cursor-pointer bg-gray-50/50 dark:bg-warmdark-bg hover:bg-blue-50/50 dark:hover:bg-blue-900/10 hover:border-blue-400 transition-all group relative overflow-hidden">
                        
                        <div id="document-default" class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                            <div class="w-12 h-12 bg-white dark:bg-warmdark-panel shadow-sm rounded-full flex items-center justify-center mb-3 group-hover:scale-110 group-hover:text-blue-500 transition-transform text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Click to upload document</p>
                            <p class="text-[11px] text-gray-400 mt-1 uppercase tracking-wide">DOCX, PDF, RTF</p>
                        </div>

                        <div id="document-selected" class="hidden flex-col items-center justify-center w-full h-full bg-blue-50/80 dark:bg-blue-900/20 absolute inset-0 text-center px-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center mb-2 text-blue-600 dark:text-blue-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span id="document-name" class="text-sm font-bold text-blue-900 dark:text-blue-200 truncate w-full px-2"></span>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 hover:underline">Click to change</p>
                        </div>

                        <input id="document-file" type="file" name="submission_file" accept=".pdf,.docx,.doc,.odt,.rtf,.txt,.pptx" class="hidden" onchange="handleFileSelect(this, 'document-default', 'document-selected', 'document-name', 'document-dropzone', 'document-error')" />
                    </label>
                    <p id="document-error" class="hidden text-red-500 dark:text-red-400 text-xs font-bold mt-2 text-center">Document is required.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-warmdark-border">
                <button type="submit" id="submitUploadBtn" class="bg-blue-600 dark:bg-blue-700 text-white px-10 py-3.5 rounded-xl text-sm font-bold shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 hover:shadow-lg transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <?= $submitText ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ORIGINAL LOADING OVERLAY -->
<div id="uploadLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 id="loadingTitle" class="text-lg font-bold text-gray-800 dark:text-gray-100">Uploading Files...</h3>
        <p id="loadingDesc" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please wait while we process your submission.</p>
    </div>
</div>

<script>
    const reqReceipt = <?= $showReceipt ? 'true' : 'false' ?>;
    const reqDocument = <?= $showDocument ? 'true' : 'false' ?>;

    function handleFileSelect(input, defaultId, selectedId, textId, dropzoneId, errorId) {
        const defaultView = document.getElementById(defaultId);
        const selectedView = document.getElementById(selectedId);
        const nameText = document.getElementById(textId);
        const dropzone = document.getElementById(dropzoneId);
        const error = document.getElementById(errorId);

        if (input.files && input.files.length > 0) {
            nameText.textContent = input.files[0].name;
            defaultView.classList.add('hidden');
            defaultView.classList.remove('flex');
            selectedView.classList.remove('hidden');
            selectedView.classList.add('flex');
            error.classList.add('hidden');
            dropzone.classList.remove('border-dashed', 'border-gray-300', 'dark:border-gray-600', 'border-red-400', 'dark:border-red-500');
            dropzone.classList.add('border-solid', 'border-blue-500', 'dark:border-blue-500');
        } else {
            defaultView.classList.remove('hidden');
            defaultView.classList.add('flex');
            selectedView.classList.add('hidden');
            selectedView.classList.remove('flex');
            dropzone.classList.add('border-dashed', 'border-gray-300', 'dark:border-gray-600');
            dropzone.classList.remove('border-solid', 'border-blue-500', 'dark:border-blue-500', 'border-red-400', 'dark:border-red-500');
        }
    }

    function validateForm(event) {
        let isValid = true;

        if (reqReceipt) {
            const receipt = document.getElementById('receipt-file');
            if (!receipt.files || receipt.files.length === 0) {
                document.getElementById('receipt-error').classList.remove('hidden');
                const rd = document.getElementById('receipt-dropzone');
                rd.classList.remove('border-gray-300', 'dark:border-gray-600');
                rd.classList.add('border-red-400', 'dark:border-red-500');
                isValid = false;
            }
        }

        if (reqDocument) {
            const doc = document.getElementById('document-file');
            if (!doc.files || doc.files.length === 0) {
                document.getElementById('document-error').classList.remove('hidden');
                const dd = document.getElementById('document-dropzone');
                dd.classList.remove('border-gray-300', 'dark:border-gray-600');
                dd.classList.add('border-red-400', 'dark:border-red-500');
                isValid = false;
            }
        }

        if(!isValid) {
            event.preventDefault();
            return false;
        }

        document.getElementById('loadingTitle').innerText = (reqReceipt && reqDocument) ? 'Uploading Files...' : 'Updating File...';
        document.getElementById('loadingDesc').innerText  = 'Please wait while we update your submission.';

        document.getElementById('uploadLoadingOverlay').classList.remove('hidden');
        document.getElementById('uploadLoadingOverlay').classList.add('flex');
        document.getElementById('submitUploadBtn').disabled = true;
        document.getElementById('submitUploadBtn').innerHTML = 'Uploading...';
        
        return true;
    }
</script>
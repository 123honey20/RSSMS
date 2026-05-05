<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// Get student id and department
$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id, department_id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];
$student_dept_id = $student['department_id'];

// --- GET ADMIN RULES TO KNOW MAX PHASES ---
$reqStmt = $conn->prepare("SELECT required_phases FROM department_service_requirements WHERE department_id = ? AND service_type = 'Human Grammarian'");
$reqStmt->bind_param("i", $student_dept_id);
$reqStmt->execute();
$reqRes = $reqStmt->get_result()->fetch_assoc();
$max_phases = $reqRes ? (int)$reqRes['required_phases'] : 1;
$reqStmt->close();

// Check if already has submission (getting the latest round)
$sub = $conn->query("SELECT * FROM human_grammarian WHERE student_id = $student_id ORDER BY phase DESC, round DESC LIMIT 1");
$existing = $sub->fetch_assoc();
$currentPhase = $existing ? (int)($existing['phase'] ?? 1) : 1;
?>

<div class="max-w-4xl mx-auto py-8 px-4 w-full transition-colors duration-200">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="fixed top-6 right-6 bg-green-600 dark:bg-green-700 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0">
            <div class="bg-green-500 dark:bg-green-600 rounded-full p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <span class="font-medium text-sm"><?php echo $_SESSION['flash_success']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-success')?.classList.add('opacity-0', 'translate-x-full'); }, 3000);</script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="toast-error" class="fixed top-6 right-6 bg-red-600 dark:bg-red-700 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0">
            <div class="bg-red-500 dark:bg-red-600 rounded-full p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <span class="font-medium text-sm"><?php echo $_SESSION['flash_error']; ?></span>
        </div>
        <script>setTimeout(() => { document.getElementById('toast-error')?.classList.add('opacity-0', 'translate-x-full'); }, 4000); </script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Human Grammarian Upload</h1>
            <?php if ($max_phases > 1): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit your document for Phase <?= $existing && $existing['status'] === 'Approved' ? $currentPhase + 1 : $currentPhase ?>.</p>
            <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit your document to review by the Human Grammarian Personnel.</p>
            <?php endif; ?>
        </div>
        <a href="student_dashboard.php?page=students_rs_human_grammarian" 
           class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-transparent dark:border-warmdark-border p-8 transition-colors">
        
        <?php if ($existing): ?>
            <div class="mb-8 p-5 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-100 dark:bg-blue-900/50 p-2.5 rounded-lg text-blue-600 dark:text-blue-400 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            <?php if ($max_phases > 1): ?>
                                Current Progress: Phase <?= $currentPhase ?> (Round <?php echo $existing['round']; ?>)
                            <?php else: ?>
                                Existing Submission Found (Round <?php echo $existing['round']; ?>)
                            <?php endif; ?>
                        </h3>
                        <?php if ($existing['status'] === 'Approved'): ?>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5 font-semibold">Ready for Phase <?= $currentPhase + 1 ?> Upload.</p>
                        <?php else: ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Uploading a new file will replace your current submission.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php
                    $status = ucfirst($existing['status']);
                    $badgeColor = "bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-400";
                    if ($status === 'Pending') $badgeColor = "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400";
                    if ($status === 'Approved') $badgeColor = "bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400";
                    if ($status === 'Needs Revision' || $status === 'Rejected') {
                        $status = 'Needs Revision';
                        $badgeColor = "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400";
                    }
                ?>
                <span class="px-4 py-1.5 text-xs font-bold rounded-full shadow-sm transition-colors <?php echo $badgeColor; ?>">
                    Status: <?php echo $status; ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="../../backend/actions/upload-submission/upload_human_grammarian.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
            
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select your document</label>
                
                <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" id="dropzone-label" class="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-2xl cursor-pointer bg-gray-50 dark:bg-warmdark-bg hover:bg-blue-50 dark:hover:bg-blue-900/10 hover:border-blue-400 dark:hover:border-blue-500 transition-all group">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-600 dark:text-gray-400"><span class="font-semibold text-blue-600 dark:text-blue-400">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">Supported formats: PDF, DOCX, DOC, ODT, RTF, TXT, PPTX</p>
                        </div>
                        <input id="dropzone-file" type="file" name="submission_file" class="hidden" onchange="updateFileName(this)" />
                    </label>
                </div>

                <p id="file-error" class="hidden text-red-500 dark:text-red-400 text-sm font-medium mt-3 text-center">
                    Please select a document before submitting.
                </p>
                
                <div id="file-display-container" class="hidden mt-4 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-900/50 rounded-lg transition-colors">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span id="file-name-text" class="text-sm font-medium text-blue-800 dark:text-blue-300 truncate"></span>
                    </div>
                    <button type="button" onclick="clearFileSelection()" class="text-blue-400 dark:text-blue-500 hover:text-red-500 dark:hover:text-red-400 p-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
                <button type="submit" id="submitUploadBtn" class="bg-blue-600 dark:bg-blue-700 text-white px-8 py-3 rounded-xl text-sm font-semibold shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 hover:shadow-lg transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <?php echo ($existing && $existing['status'] !== 'Approved') ? "Confirm Re-upload" : "Submit Document"; ?>
                </button>
            </div>

        </form>
    </div>
</div>

<!-- FULL SCREEN LOADING OVERLAY -->
<div id="uploadLoadingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Uploading Document...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please wait while we process and notify the personnel.</p>
    </div>
</div>

<script>
    function updateFileName(input) {
        const container = document.getElementById('file-display-container');
        const textDisplay = document.getElementById('file-name-text');
        const errorMsg = document.getElementById('file-error');
        const dropzoneLabel = document.getElementById('dropzone-label');
        
        if (input.files && input.files.length > 0) {
            textDisplay.textContent = input.files[0].name;
            container.classList.remove('hidden');
            container.classList.add('flex');
            
            errorMsg.classList.add('hidden');
            dropzoneLabel.classList.remove('border-red-400', 'bg-red-50', 'dark:border-red-500/50', 'dark:bg-red-900/10');
        } else {
            container.classList.add('hidden');
            container.classList.remove('flex');
        }
    }

    function clearFileSelection() {
        const input = document.getElementById('dropzone-file');
        const container = document.getElementById('file-display-container');
        input.value = "";
        container.classList.add('hidden');
        container.classList.remove('flex');
    }

    function validateForm(event) {
        const input = document.getElementById('dropzone-file');
        const errorMsg = document.getElementById('file-error');
        const dropzoneLabel = document.getElementById('dropzone-label');
        
        if (!input.files || input.files.length === 0) {
            event.preventDefault();
            errorMsg.classList.remove('hidden');
            dropzoneLabel.classList.add('border-red-400', 'bg-red-50', 'dark:border-red-500/50', 'dark:bg-red-900/10');
            return false;
        }

        document.getElementById('uploadLoadingOverlay').classList.remove('hidden');
        document.getElementById('uploadLoadingOverlay').classList.add('flex');
        
        const btn = document.getElementById('submitUploadBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Uploading...';
        }
        return true;
    }
</script>
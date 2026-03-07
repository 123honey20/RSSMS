<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// Get student id
$user_id = $_SESSION['user'];
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Check if already has submission (getting the latest round)
$sub = $conn->query("SELECT * FROM ethics WHERE student_id = $student_id ORDER BY round DESC LIMIT 1");
$existing = $sub->fetch_assoc();
?>

<div class="max-w-4xl mx-auto py-8 px-4 w-full">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="fixed top-6 right-6 bg-green-600 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50 transition-all duration-500 transform translate-x-0">
            <div class="bg-green-500 rounded-full p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <span class="font-medium text-sm"><?php echo $_SESSION['flash_success']; ?></span>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-success');
                if (toast) {
                    toast.classList.add('opacity-0', 'translate-x-full');
                    setTimeout(() => toast.remove(), 500);
                }
            }, 3000);
        </script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>


    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Ethics Upload</h1>
            <p class="text-sm text-gray-500 mt-1">Submit your document to review by the Ethics Personnel.</p>
        </div>
        <a href="student_dashboard.php?page=students_rs_ethics" 
           class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        
        <?php if ($existing): ?>
            <div class="mb-8 p-5 bg-blue-50/50 border border-blue-100 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-100 p-2.5 rounded-lg text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Existing Submission Found (Round <?php echo $existing['round']; ?>)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Uploading a new file will replace your current submission round.</p>
                    </div>
                </div>
                
                <?php
                    $status = ucfirst($existing['status']);
                    $badgeColor = "bg-gray-100 text-gray-700";
                    if ($status === 'Pending') $badgeColor = "bg-yellow-100 text-yellow-700";
                    if ($status === 'Approved') $badgeColor = "bg-green-100 text-green-700";
                    if ($status === 'Rejected') $badgeColor = "bg-red-100 text-red-700";
                ?>
                <span class="px-4 py-1.5 text-xs font-bold rounded-full shadow-sm <?php echo $badgeColor; ?>">
                    Status: <?php echo $status; ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="../../backend/actions/upload-submission/upload_ethics.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
            
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-3">Select your document</label>
                
                <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" id="dropzone-label" class="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer bg-gray-50 hover:bg-blue-50 hover:border-blue-400 transition-all group">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-12 h-12 mb-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-600"><span class="font-semibold text-blue-600">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500">Supported formats: PDF, DOCX, DOC, ODT, RTF, TXT, PPTX</p>
                        </div>
                        <input id="dropzone-file" type="file" name="submission_file" class="hidden" onchange="updateFileName(this)" />
                    </label>
                </div>

                <p id="file-error" class="hidden text-red-500 text-sm font-medium mt-3 text-center">
                    Please select a document before submitting.
                </p>
                
                <div id="file-display-container" class="hidden mt-4 flex items-center justify-between p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span id="file-name-text" class="text-sm font-medium text-blue-800 truncate"></span>
                    </div>
                    <button type="button" onclick="clearFileSelection()" class="text-blue-400 hover:text-red-500 p-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl text-sm font-semibold shadow-md hover:bg-blue-700 hover:shadow-lg transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <?php echo $existing ? "Confirm Re-upload" : "Submit Document"; ?>
                </button>
            </div>

        </form>
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
            
            // Hide error state if they select a file
            errorMsg.classList.add('hidden');
            dropzoneLabel.classList.remove('border-red-400', 'bg-red-50');
        } else {
            container.classList.add('hidden');
        }
    }

    // Allows the user to clear their file selection
    function clearFileSelection() {
        const input = document.getElementById('dropzone-file');
        const container = document.getElementById('file-display-container');
        
        input.value = ""; // Clear the file input
        container.classList.add('hidden'); // Hide the display container
    }

    // Custom Form Validation
    function validateForm(event) {
        const input = document.getElementById('dropzone-file');
        const errorMsg = document.getElementById('file-error');
        const dropzoneLabel = document.getElementById('dropzone-label');
        
        // If no file is selected
        if (!input.files || input.files.length === 0) {
            event.preventDefault(); // Stop form from submitting
            
            // Show custom error text and turn the dropzone red
            errorMsg.classList.remove('hidden');
            dropzoneLabel.classList.add('border-red-400', 'bg-red-50');
            
            return false;
        }
        
        return true;
    }
</script>
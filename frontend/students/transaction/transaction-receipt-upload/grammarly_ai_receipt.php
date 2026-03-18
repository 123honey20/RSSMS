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
$round = isset($_GET['round']) ? intval($_GET['round']) : 0;

// Get student id
$res = $conn->query("SELECT id FROM students WHERE user_id = $user_id");
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Get transaction info for this round
$stmt = $conn->prepare("SELECT * FROM grammarly_ai_transactions WHERE student_id = ? AND round = ? LIMIT 1");
$stmt->bind_param("ii", $student_id, $round);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    echo "<div class='max-w-4xl mx-auto py-8 px-4'><div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/30 rounded-lg'>Transaction not found.</div></div>";
    exit();
}

$status = ucfirst($transaction['status']);
$canUpload = in_array($status, ['No Receipt', 'Rejected']);
?>

<div class="max-w-4xl mx-auto py-8 px-4 w-full transition-colors duration-200">

    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Upload Transaction Receipt</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit your payment receipt for Round <?php echo $round; ?>.</p>
        </div>
        <a href="student_dashboard.php?page=student_transaction_grammarly_ai" 
           class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Transactions
        </a>
    </div>

    <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border p-8 transition-colors">
        
        <div class="mb-8 p-5 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 dark:bg-blue-900/50 p-2.5 rounded-lg text-blue-600 dark:text-blue-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Receipt Details (Round <?php echo $round; ?>)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Please ensure your receipt is clear and readable.</p>
                </div>
            </div>
            
            <?php
                $badgeColor = "bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-400";
                if ($status === 'No Receipt') $badgeColor = "bg-gray-100 dark:bg-warmdark-bg text-gray-700 dark:text-gray-400";
                if ($status === 'Receipt Uploaded') $badgeColor = "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400";
                if ($status === 'Approved') $badgeColor = "bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400";
                if ($status === 'Rejected') $badgeColor = "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400";
            ?>
            <span class="px-4 py-1.5 text-xs font-bold rounded-full shadow-sm transition-colors <?php echo $badgeColor; ?>">
                Status: <?php echo $status; ?>
            </span>
        </div>

        <?php if ($canUpload): ?>
            <form action="../../backend/actions/upload-receipt/upload_grammarly_ai_receipt.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
                
                <input type="hidden" name="round" value="<?php echo $round; ?>">

                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select your receipt file</label>
                    
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file" id="dropzone-label" class="flex flex-col items-center justify-center w-full h-56 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-2xl cursor-pointer bg-gray-50 dark:bg-warmdark-bg hover:bg-blue-50 dark:hover:bg-blue-900/10 hover:border-blue-400 dark:hover:border-blue-500 transition-all group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400"><span class="font-semibold text-blue-600 dark:text-blue-400">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Supported formats: JPG, PNG, PDF</p>
                            </div>
                            <input id="dropzone-file" type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf,.txt" class="hidden" onchange="updateFileName(this)" />
                        </label>
                    </div>

                    <p id="file-error" class="hidden text-red-500 dark:text-red-400 text-sm font-medium mt-3 text-center">
                        Please select a receipt file before submitting.
                    </p>
                    
                    <div id="file-display-container" class="hidden mt-4 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-900/50 rounded-lg transition-colors">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span id="file-name-text" class="text-sm font-medium text-blue-800 dark:text-blue-300 truncate"></span>
                        </div>
                        <button type="button" onclick="clearFileSelection()" class="text-blue-400 dark:text-blue-500 hover:text-red-500 dark:hover:text-red-400 p-1 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-warmdark-border transition-colors">
                    <button type="submit" class="bg-blue-600 dark:bg-blue-700 text-white px-8 py-3 rounded-xl text-sm font-semibold shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 hover:shadow-lg transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Upload Receipt
                    </button>
                </div>

            </form>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-2xl transition-colors">
                <div class="bg-gray-200 dark:bg-warmdark-panel p-4 rounded-full mb-4 transition-colors">
                    <?php if ($status === 'Approved'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600 dark:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    <?php endif; ?>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">Upload Disabled</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center max-w-sm">
                    <?php if ($status === 'Approved'): ?>
                        Your receipt for this round has already been approved. You can now proceed to submit your document.
                    <?php else: ?>
                        Your receipt is currently being reviewed. Please wait for the personnel to approve or reject it.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Handles displaying the file name nicely when the user selects a file
    function updateFileName(input) {
        const container = document.getElementById('file-display-container');
        const textDisplay = document.getElementById('file-name-text');
        const errorMsg = document.getElementById('file-error');
        const dropzoneLabel = document.getElementById('dropzone-label');
        
        if (input.files && input.files.length > 0) {
            textDisplay.textContent = input.files[0].name;
            container.classList.remove('hidden');
            container.classList.add('flex');
            
            // Hide error state if they select a file
            errorMsg.classList.add('hidden');
            dropzoneLabel.classList.remove('border-red-400', 'bg-red-50', 'dark:border-red-500/50', 'dark:bg-red-900/10');
        } else {
            container.classList.add('hidden');
            container.classList.remove('flex');
        }
    }

    // Allows the user to clear their file selection
    function clearFileSelection() {
        const input = document.getElementById('dropzone-file');
        const container = document.getElementById('file-display-container');
        
        input.value = ""; // Clear the file input
        container.classList.add('hidden'); // Hide the display container
        container.classList.remove('flex');
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
            dropzoneLabel.classList.add('border-red-400', 'bg-red-50', 'dark:border-red-500/50', 'dark:bg-red-900/10');
            
            return false;
        }
        
        return true;
    }
</script>
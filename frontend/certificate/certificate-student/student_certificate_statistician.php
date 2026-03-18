<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

// Verify the submission belongs to the student and is Approved
$stmt = $conn->prepare("
    SELECT st.*, s.control_number 
    FROM statistician st
    JOIN students s ON st.student_id = s.id
    WHERE st.id = ? AND s.user_id = ? AND st.status = 'Approved'
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/30 rounded-lg max-w-4xl mx-auto mt-8'>Certificate not found or unauthorized access.</div>";
    exit();
}

// NEW: Dynamic Certificate Checking
$cert_dir = "../images/certificates/statistician-certificate/";
$cert_ext = "jpg"; // fallback
$cert_type = "image";

if (file_exists($cert_dir . "certificate-statistician.pdf")) {
    $cert_ext = "pdf";
    $cert_type = "pdf";
} elseif (file_exists($cert_dir . "certificate-statistician.png")) {
    $cert_ext = "png";
}

$certificateImagePath = $cert_dir . "certificate-statistician." . $cert_ext . "?v=" . time();
$downloadFileName = "Statistician_Certificate_" . htmlspecialchars($submission['control_number']) . "." . $cert_ext;
?>

<div class="max-w-5xl mx-auto pb-12 pt-6 transition-colors duration-200">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Approval Certificate</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Official Statistician Clearance Document</p>
        </div>
        <a href="student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $submissionId; ?>"
            class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Result
        </a>
    </div>

    <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-lg border border-gray-200 dark:border-warmdark-border overflow-hidden flex flex-col print:shadow-none print:border-none transition-colors">
        <div class="bg-gray-50 dark:bg-warmdark-bg border-b border-gray-200 dark:border-warmdark-border px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 print:hidden transition-colors">
            <div class="flex items-center gap-3">
                <span class="bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider shadow-sm transition-colors">Verified</span>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 transition-colors">Control No: <span class="text-gray-900 dark:text-gray-200"><?php echo htmlspecialchars($submission['control_number']); ?></span></span>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <?php if ($cert_type === 'pdf'): ?>
                    <button onclick="printPDF('<?php echo $certificateImagePath; ?>')" class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print PDF
                    </button>
                <?php else: ?>
                    <button onclick="window.print()" class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Image
                    </button>
                <?php endif; ?>
                
                <a href="<?php echo $certificateImagePath; ?>" download="<?php echo $downloadFileName; ?>" 
                   class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-blue-900 dark:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-800 dark:hover:bg-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download
                </a>
            </div>
        </div>

        <div class="p-6 sm:p-12 bg-gray-200/80 dark:bg-black/50 flex justify-center items-center min-h-[600px] print:p-0 print:bg-white print:min-h-0 transition-colors">
            <div id="printable-certificate" class="bg-white shadow-2xl border border-gray-300 dark:border-warmdark-border w-full max-w-4xl aspect-[1.414] relative flex items-center justify-center overflow-hidden">
                <?php if ($cert_type === 'pdf'): ?>
                    <iframe id="pdfIframe" src="<?php echo $certificateImagePath; ?>" class="w-full h-[800px] border-0 print:h-full block bg-white"></iframe>
                <?php else: ?>
                    <img src="<?php echo $certificateImagePath; ?>" alt="Statistician Certificate" class="w-full h-full object-contain bg-white"
                         onerror="this.onerror=null; this.src='https://placehold.co/1122x793/ffffff/1e3a8a?font=montserrat&text=Certificate+Preview\\nControl+No:+<?php echo htmlspecialchars($submission['control_number']); ?>\\n\\n(File+Not+Found)';">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-certificate, #printable-certificate * { visibility: visible; }
        #printable-certificate {
            position: absolute; left: 0; top: 0; width: 100%; height: 100vh; max-width: 100%; margin: 0; padding: 0; box-shadow: none !important; border: none !important; transform: none !important;
        }
        html, body { overflow: hidden; background: white; height: 100%; margin: 0; }
    }
</style>

<script>
function printPDF(pdfUrl) {
    const printWindow = window.open(pdfUrl, '_blank');
    if (printWindow) {
        printWindow.onload = function() {
            printWindow.print();
        };
    } else {
        alert("Please allow popups for this site to print the PDF, or use the Download button and print from your computer.");
    }
}
</script>
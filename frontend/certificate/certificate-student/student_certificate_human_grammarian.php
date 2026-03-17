<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

// Verify the submission belongs to the student and is Approved
$stmt = $conn->prepare("
    SELECT hg.*, s.control_number 
    FROM human_grammarian hg
    JOIN students s ON hg.student_id = s.id
    WHERE hg.id = ? AND s.user_id = ? AND hg.status = 'Approved'
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg max-w-4xl mx-auto mt-8'>Certificate not found or unauthorized access.</div>";
    exit();
}

// NEW: Dynamic Certificate Checking
$cert_dir = "../images/certificates/human-grammarian-certificate/";
$cert_ext = "jpg"; // fallback
$cert_type = "image";

if (file_exists($cert_dir . "certificate-human-grammarian.pdf")) {
    $cert_ext = "pdf";
    $cert_type = "pdf";
} elseif (file_exists($cert_dir . "certificate-human-grammarian.png")) {
    $cert_ext = "png";
}

$certificateImagePath = $cert_dir . "certificate-human-grammarian." . $cert_ext . "?v=" . time();
$downloadFileName = "Human_Grammarian_Certificate_" . htmlspecialchars($submission['control_number']) . "." . $cert_ext;
?>

<div class="max-w-5xl mx-auto pb-12 pt-6">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Approval Certificate</h1>
            <p class="text-sm text-gray-500 mt-1">Official Human Grammarian Clearance Document</p>
        </div>
        <a href="student_dashboard.php?page=student_human_grammarian_approved_result&id=<?php echo $submissionId; ?>"
            class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Result
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden flex flex-col print:shadow-none print:border-none">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider shadow-sm">Verified</span>
                <span class="text-sm font-semibold text-gray-600">Control No: <span class="text-gray-900"><?php echo htmlspecialchars($submission['control_number']); ?></span></span>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <?php if ($cert_type === 'pdf'): ?>
                    <button onclick="printPDF('<?php echo $certificateImagePath; ?>')" class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 hover:text-blue-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print PDF
                    </button>
                <?php else: ?>
                    <button onclick="window.print()" class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 hover:text-blue-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Image
                    </button>
                <?php endif; ?>
                
                <a href="<?php echo $certificateImagePath; ?>" download="<?php echo $downloadFileName; ?>" 
                   class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-blue-900 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-blue-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download
                </a>
            </div>
        </div>

        <div class="p-6 sm:p-12 bg-gray-200/80 flex justify-center items-center min-h-[600px] print:p-0 print:bg-white print:min-h-0">
            <div id="printable-certificate" class="bg-white shadow-2xl border border-gray-300 w-full max-w-4xl aspect-[1.414] relative flex items-center justify-center overflow-hidden">
                <?php if ($cert_type === 'pdf'): ?>
                    <iframe id="pdfIframe" src="<?php echo $certificateImagePath; ?>" class="w-full h-[800px] border-0 print:h-full block"></iframe>
                <?php else: ?>
                    <img src="<?php echo $certificateImagePath; ?>" alt="Human Grammarian Certificate" class="w-full h-full object-contain"
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
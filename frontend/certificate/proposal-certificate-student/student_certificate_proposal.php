<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$userId = $_SESSION['user'];

$stmt = $conn->prepare("SELECT id, control_number FROM students WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$student_res = $stmt->get_result()->fetch_assoc();
$student_id = $student_res ? $student_res['id'] : 0;
$control_number = $student_res ? $student_res['control_number'] : 'UNKNOWN';
$stmt->close();

$services = ['grammarly_ai', 'ethics', 'human_grammarian', 'librarian', 'statistician'];
$isEligible = true;

if ($student_id > 0) {
    foreach ($services as $service) {
        $checkStmt = $conn->prepare("SELECT id FROM $service WHERE student_id = ? AND status = 'Approved' LIMIT 1");
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $isEligible = false;
            $checkStmt->close();
            break; 
        }
        $checkStmt->close();
    }
} else {
    $isEligible = false;
}

if (!$isEligible) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg max-w-4xl mx-auto mt-8'>You have not yet met the requirements to unlock the Proposal Certificate.</div>";
    exit();
}

// Ensure you placed the image in this exact location!
$certificateImagePath = "../images/certificates/proposal-certificate/certificate-proposal-student.png"; 
?>

<div class="max-w-5xl mx-auto pb-12 pt-6">

    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Proposal Certificate</h1>
            </div>
            <p class="text-sm text-gray-500">Congratulations! You have completed all required research clearances.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-yellow-300 overflow-hidden flex flex-col print:shadow-none print:border-none">
        
        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-b border-yellow-200 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <span class="bg-yellow-400 text-yellow-900 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider shadow-sm">Fully Cleared</span>
                <span class="text-sm font-semibold text-yellow-900">Control No: <span class="text-gray-900"><?php echo htmlspecialchars($control_number); ?></span></span>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button onclick="window.print()" class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-white border border-yellow-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-yellow-100 hover:text-yellow-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                
                <a href="<?php echo $certificateImagePath; ?>" download="Proposal_Certificate_<?php echo htmlspecialchars($control_number); ?>.jpg" 
                   class="flex-1 sm:flex-none flex justify-center items-center gap-2 bg-gradient-to-r from-amber-400 to-yellow-500 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-md hover:from-amber-500 hover:to-yellow-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download
                </a>
            </div>
        </div>

        <div class="p-6 sm:p-12 bg-gray-200/80 flex justify-center items-center min-h-[600px] print:p-0 print:bg-white print:min-h-0">
            <div id="printable-certificate" class="bg-white shadow-2xl border border-gray-300 w-full max-w-4xl aspect-[1.414] relative flex items-center justify-center overflow-hidden">
                <img src="<?php echo $certificateImagePath; ?>" 
                     alt="Proposal Certificate" 
                     class="w-full h-full object-contain"
                     onerror="this.onerror=null; this.src='https://placehold.co/1122x793/ffffff/d97706?font=montserrat&text=Proposal+Certificate\\nControl+No:+<?php echo htmlspecialchars($control_number); ?>\\n\\n(Image+Not+Found)';">
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-certificate, #printable-certificate * { visibility: visible; }
        #printable-certificate {
            position: absolute; left: 0; top: 0; width: 100%; height: 100%; margin: 0; padding: 0; box-shadow: none !important; border: none !important;
        }
        html, body { overflow: hidden; }
    }
</style>
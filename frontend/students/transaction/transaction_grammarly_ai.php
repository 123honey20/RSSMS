<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$student_id = $student['id'];

// Get all transactions
$transactions = $conn->query("
    SELECT * FROM grammarly_ai_transactions
    WHERE student_id = $student_id
    ORDER BY round DESC
");

$maxRound = 7;

$latestTransactionRes = $conn->query("
    SELECT * FROM grammarly_ai_transactions
    WHERE student_id = $student_id
    ORDER BY round DESC
    LIMIT 1
");

$latestTransaction = $latestTransactionRes->fetch_assoc();
$canCreateNewRound = false;

if (!$latestTransaction) {
    // First time student
    $canCreateNewRound = true;
} else {
    $latestRound = (int)$latestTransaction['round'];
    $latestStatus = $latestTransaction['status'];

    // Only check if we haven't exceeded max rounds
    if ($latestRound < $maxRound) {
        // Check if there is already a SUBMISSION for this latest round
        $subCheck = $conn->query("
            SELECT status FROM grammarly_ai 
            WHERE student_id = $student_id AND round = $latestRound 
            LIMIT 1
        ");
        $latestSubmission = $subCheck->fetch_assoc();
        // If a submission exists for this round AND it was Rejected,
        // ONLY THEN can they request a new receipt transaction for Round 2.
        if ($latestSubmission && $latestSubmission['status'] === 'Rejected') {
            $canCreateNewRound = true;
        }
    }
}
?>

<div class="max-w-6xl mx-auto py-8 px-4 w-full">

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


    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Transaction History</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your payment receipts for the Grammarly & AI Checking Service.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200 tracking-wider">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Round</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Date Created</th>
                        <th class="px-6 py-4 font-semibold text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                
                                <td class="px-6 py-5 font-medium text-xs text-gray-800">
                                    Round <?php echo (int)$row['round']; ?>
                                </td>

                                <td class="px-5 py-5">
                                    <?php
                                    $status = $row['status'];
                                    $badgeColor = "text-gray-700";
                                    if ($status === "Approved") $badgeColor = "text-green-700";
                                    if ($status === "Rejected") $badgeColor = "text-red-700";
                                    if ($status === "Receipt Uploaded") $badgeColor = "text-yellow-700";
                                    ?>
                                    <span class="py-1.5 text-xs font-bold <?php echo $badgeColor; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-gray-500">
                                    <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                </td>

                                <td class="px-6 py-5 text-center">
                                    <?php if ($status === 'No Receipt' || $status === 'Rejected'): ?>
                                        <a href="student_dashboard.php?page=student_transaction_receipt_grammarly_ai&round=<?php echo $row['round']; ?>"
                                            class="inline-flex items-center justify-center bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-sm hover:bg-blue-700 transition-colors gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            <?php echo $status === 'Rejected' ? 'Re-upload Receipt' : 'Upload Receipt'; ?>
                                        </a>
                                    
                                    <?php elseif ($status === 'Approved'): ?>
                                        <span class="inline-flex items-center justify-center bg-gray-50 text-gray-400 border border-gray-200 px-4 py-2 rounded-lg text-xs font-semibold gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Completed
                                        </span>
                                    
                                    <?php else: ?>
                                        <span class="inline-flex items-center justify-center bg-gray-50 text-gray-500 border border-gray-200 px-4 py-2 rounded-lg text-xs font-semibold gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Under Review
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-500">No transaction records found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="p-6 border-t border-gray-100 bg-gray-50/50">
            <?php if ($canCreateNewRound): ?>
                <form method="POST" action="../../backend/actions/create-transaction/create_grammarly_ai_transaction.php">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-md hover:bg-green-700 hover:shadow-lg transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Request New Round
                    </button>
                </form>
            <?php else: ?>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <div class="bg-gray-200 p-1.5 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span>You must complete your current submission and wait.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
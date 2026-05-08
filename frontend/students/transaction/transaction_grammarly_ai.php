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

// Get all transactions purely as a history log
$transactions = $conn->query("
    SELECT * FROM grammarly_ai_transactions
    WHERE student_id = $student_id
    ORDER BY round DESC
");

$hasAnyTransaction = $transactions->num_rows > 0;
?>

<div class="max-w-6xl mx-auto py-8 px-4 w-full transition-colors duration-200">

    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Transaction History</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">A history of your payment receipts for Grammarly & AI Checking.</p>
        </div>
        <a href="student_dashboard.php?page=students_rs_grammarly_ai" class="bg-gray-100 dark:bg-warmdark-panel text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-warmdark-border px-5 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-warmdark-hover transition-colors">
            Back to Dashboard
        </a>
    </div>

    <?php if (!$hasAnyTransaction): ?>
        <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border p-12 flex flex-col items-center justify-center text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">No Transactions Yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">When you upload a submission, your receipt history will appear here.</p>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border tracking-wider transition-colors">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Round</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Date Uploaded</th>
                            <th class="px-6 py-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors">
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                                
                                <td class="px-6 py-5 font-medium text-xs text-gray-800 dark:text-gray-200">
                                    Round <?php echo (int)$row['round']; ?>
                                </td>

                                <td class="px-5 py-5">
                                    <?php
                                    $status = $row['status'];
                                    $badgeColor = "text-gray-700 dark:text-gray-400";
                                    if ($status === "Approved") $badgeColor = "text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full border border-green-200 dark:border-green-900/30";
                                    if ($status === "Needs Revision") $badgeColor = "text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-3 py-1 rounded-full border border-red-200 dark:border-red-900/30";
                                    if ($status === "Receipt Uploaded" || $status === "Pending") $badgeColor = "text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 px-3 py-1 rounded-full border border-yellow-200 dark:border-yellow-900/30";
                                    ?>
                                    <span class="text-xs font-bold <?php echo $badgeColor; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-gray-500 dark:text-gray-400">
                                    <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                </td>

                                <td class="px-6 py-5 text-center">
                                    <?php if (!empty($row['receipt_path'])): ?>
                                        <a href="../../uploads/grammarly_ai/receipts/<?php echo $row['receipt_path']; ?>" target="_blank"
                                            class="inline-flex items-center justify-center bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-100 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-900/50 transition-colors gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Receipt
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">No receipt attached</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
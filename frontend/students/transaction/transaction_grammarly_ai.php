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

$stmt2 = $conn->prepare("SELECT school_id FROM users WHERE id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$userRow = $res2->fetch_assoc();
$school_id = $userRow['school_id'];

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

    if ($latestStatus === 'Approved' && $latestRound < $maxRound) {
        $canCreateNewRound = true;
    }
}
?>

<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-md font-semibold text-gray-800 mb-4">
            Transaction History of Grammarly & AI Checking Service
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs uppercase text-gray-500 border-b">
                    <tr>
                        <th class="py-2">Submission</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Date</th>
                        <th class="py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="py-3 text-xs">Round <?php echo (int)$row['round']; ?></td>

                                <td class="py-3">
                                    <?php
                                    $statusColor = "text-gray-600";
                                    if ($row['status'] === "Approved") $statusColor = "text-green-600";
                                    if ($row['status'] === "Rejected") $statusColor = "text-red-600";
                                    if ($row['status'] === "Receipt Uploaded") $statusColor = "text-yellow-600";
                                    ?>
                                    <span class="font-semibold <?php echo $statusColor; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>

                                <td class="py-3">
                                    <?php echo $row['created_at']; ?>
                                </td>

                                <td class="py-3 text-center">
                                    <?php if ($row['status'] === 'No Receipt' || $row['status'] === 'Rejected'): ?>
                                        <a href="student_dashboard.php?page=student_transaction_receipt_grammarly_ai&round=<?php echo $row['round']; ?>"
                                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                            Upload Receipt
                                        </a>
                                    <?php else: ?>
                                        <span class="bg-gray-300 text-gray-600 px-3 py-1 rounded text-xs cursor-not-allowed">
                                            Waiting
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500">
                                No transaction records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $canCreateNewRound = false;

            if (!$latestTransaction) {

                // No transaction yet
                $canCreateNewRound = true;
            } else {

                $latestRound = (int)$latestTransaction['round'];
                $latestStatus = $latestTransaction['status'];

                if ($latestStatus === 'Approved' && $latestRound < $maxRound) {
                    $canCreateNewRound = true;
                }
            }
            ?>

            <div class="mt-6">
                <?php if ($canCreateNewRound): ?>
                    <form method="POST" action="../../backend/actions/create-transaction/create_grammarly_ai_transaction.php">
                        <button type="submit"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Request New Round
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-sm text-gray-500">
                        You must complete and approve the previous round before requesting a new one.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
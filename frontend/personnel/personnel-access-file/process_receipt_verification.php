<?php
require_once "../../../backend/config/database.php";

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
SELECT 
t.id,
t.receipt_path,
t.status,
t.round,
s.control_number,
d.name AS department_name
FROM grammarly_ai_transactions t
JOIN students s ON t.student_id = s.id
JOIN departments d ON s.department_id = d.id
WHERE t.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$relativePath = "/RSSMS/uploads/grammarly_ai/receipts/" . $data['receipt_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

$currentStatus = $data['status'] ?? 'Receipt Uploaded';
?>

<div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-lg p-8 space-y-8">

    <!-- HEADER -->
    <div class="flex justify-between items-start border-b pb-6">

        <div>
            <h2 class="text-2xl font-semibold text-gray-800">
                Receipt Verification
            </h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">

                <div class="min-w-0">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Control Number
                    </p>
                    <p class="font-semibold text-gray-800 break-words">
                        <?php echo $data['control_number']; ?>
                    </p>
                </div>

                <div class="min-w-0">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Department
                    </p>
                    <p class="font-semibold text-gray-800 break-words">
                        <?php echo $data['department_name']; ?>
                    </p>
                </div>

                <div class="min-w-0">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Round
                    </p>
                    <p class="font-semibold text-gray-800">
                        Round <?php echo $data['round']; ?>
                    </p>
                </div>

            </div>
        </div>

        <!-- STATUS ACTIONS -->
        <div class="flex flex-col items-end gap-3">

            <div class="text-xs font-semibold px-3 py-1 rounded-full
                    <?php
                    if ($currentStatus == 'Approved') echo 'text-green-700';
                    elseif ($currentStatus == 'Rejected') echo 'text-red-700';
                    else echo 'text-yellow-700'; ?>">
                <?php echo $currentStatus; ?>
            </div>

            <?php if ($currentStatus === 'Receipt Uploaded'): ?>
                <div class="flex gap-3">
                    <button
                        onclick="updateReceiptStatus(<?php echo $data['id']; ?>,'Approved')"
                        class="px-5 py-2 text-xs font-medium transition text-blue-700 hover:underline">
                        Approve
                    </button>
                    <span>-</span>
                    <button
                        onclick="updateReceiptStatus(<?php echo $data['id']; ?>,'Rejected')"
                        class="px-5 py-2 text-xs font-medium transition text-red-700 hover:underline">
                        Reject
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <!-- RECEIPT FILE -->
    <div>

        <p class="text-gray-400 text-xs uppercase tracking-wide mb-3">
            Receipt File
        </p>

        <?php if (file_exists($absolutePath)): ?>

            <div class="rounded-xl overflow-hidden border shadow-sm">

                <iframe
                    src="<?php echo $relativePath; ?>"
                    class="w-full h-[650px] bg-gray-50"
                    frameborder="0">
                </iframe>
            </div>
        <?php else: ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg">
                File not found.
            </div>
        <?php endif; ?>
    </div>
    <div class="flex justify-end pt-6 border-t">
        <button
            onclick="location.reload()"
            class="bg-gray-800 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-900 transition">
            Back
        </button>
    </div>

</div>
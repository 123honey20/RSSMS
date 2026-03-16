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
                        <?php echo htmlspecialchars($data['control_number']); ?>
                    </p>
                </div>

                <div class="min-w-0">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Department
                    </p>
                    <p class="font-semibold text-gray-800 break-words">
                        <?php echo htmlspecialchars($data['department_name']); ?>
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

        <div class="flex flex-col items-end gap-3">

            <div class="text-xs font-bold px-4 py-1.5 rounded-full border
                    <?php
                    if ($currentStatus == 'Approved') echo 'text-green-700 bg-green-50 border-green-200';
                    elseif ($currentStatus == 'Rejected') echo 'text-red-700 bg-red-50 border-red-200';
                    else echo 'text-yellow-700 bg-yellow-50 border-yellow-200'; ?>">
                <?php echo $currentStatus; ?>
            </div>

            <?php if ($currentStatus === 'Receipt Uploaded'): ?>
                <div class="flex gap-3">
                    <button
                        onclick="updateReceiptStatus(<?php echo $data['id']; ?>,'Approved')"
                        class="px-5 py-2 text-xs font-bold bg-gray-100 rounded-lg transition text-blue-700 hover:bg-blue-50 border border-gray-200">
                        Approve
                    </button>
                    <button
                        onclick="updateReceiptStatus(<?php echo $data['id']; ?>,'Rejected')"
                        class="px-5 py-2 text-xs font-bold bg-gray-100 rounded-lg transition text-red-700 hover:bg-red-50 border border-gray-200">
                        Reject
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div>
        <div class="flex justify-between items-center mb-3">
            <p class="text-gray-400 text-xs uppercase tracking-wide">
                Receipt File
            </p>
            
            <?php if (file_exists($absolutePath)): ?>
                <a href="<?= htmlspecialchars($relativePath) ?>" download="<?= htmlspecialchars(basename($data['receipt_path'])) ?>" class="flex items-center gap-2 bg-blue-50 text-blue-700 hover:bg-blue-100 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download Receipt
                </a>
            <?php endif; ?>
        </div>

        <?php if (file_exists($absolutePath)): ?>
            <div class="rounded-xl overflow-hidden border shadow-sm">
                <iframe
                    src="<?php echo $relativePath; ?>"
                    class="w-full h-[650px] bg-gray-50"
                    frameborder="0">
                </iframe>
            </div>
        <?php else: ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg font-medium">
                File not found on server.
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
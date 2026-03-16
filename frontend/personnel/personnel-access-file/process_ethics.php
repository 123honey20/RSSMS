<?php
session_start();
require_once "../../../backend/config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    echo "Unauthorized access!";
    exit();
}

$submissionId = intval($_GET['id']);
$viewOnly = isset($_GET['viewOnly']);

$stmt = $conn->prepare("
    SELECT e.id, e.file_path, e.status, s.control_number
    FROM ethics e
    JOIN students s ON e.student_id = s.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<p class='text-red-500'>Submission not found.</p>";
    exit();
}

$relativePath = "/RSSMS/uploads/ethics/" . $submission['file_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

$currentStatus = $submission['status'] ?? 'Pending';

// Get comment count
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total_comments
    FROM ethics_comments
    WHERE ethics_id = ?
");
$countStmt->bind_param("i", $submissionId);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalComments = $countResult['total_comments'] ?? 0;

?>
<div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-lg p-8 space-y-8">
    <div class="flex justify-between items-start border-b pb-6">

        <div>
            <h2 class="text-2xl font-semibold text-gray-800">
                Submission Review
            </h2>
            <div class="mt-4 space-y-2 text-sm">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Control Number
                    </p>
                    <p class="font-semibold text-gray-800">
                        <?= htmlspecialchars($submission['control_number']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-end gap-3">
            <div class="text-right">
                <input type="hidden" id="initialCommentCount" value="<?= $totalComments ?>">
                <span id="commentHeaderCount" class="text-blue-700 text-xs font-bold">
                    <?php if (!$viewOnly): ?>You Currently Added<?php endif; ?> <?= $totalComments ?> Comment<?= $totalComments != 1 ? 's' : '' ?>
                </span>
            </div>

            <div class="text-xs font-bold px-4 py-1.5 rounded-full border
                    <?php
                    if ($currentStatus == 'Approved') echo 'text-green-700 bg-green-50 border-green-200';
                    elseif ($currentStatus == 'Rejected') echo 'text-red-700 bg-red-50 border-red-200';
                    else echo 'text-yellow-700 bg-yellow-50 border-yellow-200'; ?>">
                <?php echo $currentStatus; ?>
            </div>

            <?php if (!$viewOnly && $currentStatus === 'Pending'): ?>
                <div class="flex gap-3">
                    <button
                        class="btn-approve px-5 py-2 text-xs font-bold bg-gray-100 rounded-lg transition text-blue-700 hover:bg-blue-50 border border-gray-200"
                        data-id="<?= $submission['id'] ?>">
                        Approve
                    </button>
                    <button
                        class="btn-reject px-5 py-2 text-xs font-bold bg-gray-100 rounded-lg transition text-red-700 hover:bg-red-50 border border-gray-200"
                        data-id="<?= $submission['id'] ?>">
                        Reject
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div>
        <div class="flex justify-between items-center mb-3">
            <p class="text-gray-400 text-xs uppercase tracking-wide">
                Submission File
            </p>
            <?php if (file_exists($absolutePath)): ?>
                <a href="<?= htmlspecialchars($relativePath) ?>" download="<?= htmlspecialchars(basename($submission['file_path'])) ?>" class="flex items-center gap-2 bg-blue-50 text-blue-700 hover:bg-blue-100 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download Document
                </a>
            <?php endif; ?>
        </div>

        <?php if (file_exists($absolutePath)): ?>
            <div class="rounded-xl overflow-hidden border shadow-sm relative">
                <iframe src="<?= htmlspecialchars($relativePath) ?>"
                    class="w-full h-[650px] bg-gray-50 relative z-10"
                    frameborder="0">
                </iframe>
            </div>
        <?php else: ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg font-medium">
                File not found.
            </div>
        <?php endif; ?>
    </div>

    <div class="flex justify-between pt-6 border-t">
        <div>
            <button
                id="viewCommentBtn"
                onclick="openViewCommentModal(<?= $submission['id'] ?>)"
                class="px-6 py-2.5 rounded-lg text-sm font-medium shadow transition bg-blue-600 text-white hover:bg-blue-700 <?= $totalComments == 0 ? 'hidden' : '' ?>">
                View Comment
            </button>
        </div>
        <div class="flex items-center gap-3">
            <button
                onclick="location.reload()"
                class="bg-gray-200 text-gray-800 px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-300 transition">
                Back
            </button>
            <?php if (!$viewOnly): ?>
                <button
                    onclick="openCommentModal(<?= $submission['id'] ?>)"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-900 transition">
                    Add Comment
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="commentModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-[500px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Add Comment</h3>
        <div id="commentError" class="hidden text-red-600 text-sm mt-2">Please complete all fields.</div>
        <div id="commentCounter" class="text-sm text-gray-500">Comment No. <span><?= $totalComments ?></span></div>
        <div>
            <label class="text-sm text-gray-600">Page Number</label>
            <input type="number" id="commentPage" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label class="text-sm text-gray-600">Paragraph Number</label>
            <input type="number" id="commentParagraph" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label class="text-sm text-gray-600">Comment</label>
            <textarea id="commentText" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" rows="4"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-3">
            <button onclick="closeCommentModal()" class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</button>
            <button id="saveCommentBtn" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700">Done</button>
        </div>
    </div>
</div>

<div id="viewCommentModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-[600px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Submitted Comments</h3>
        <div id="viewCommentList" class="max-h-[400px] overflow-y-auto space-y-3 text-sm custom-scrollbar pr-2">
            </div>
        <div class="flex justify-end pt-3">
            <button onclick="closeViewCommentModal()" class="bg-gray-800 text-white px-5 py-2 rounded-lg text-sm hover:bg-gray-900">Close</button>
        </div>
    </div>
</div>
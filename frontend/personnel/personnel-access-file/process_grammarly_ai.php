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
    SELECT g.id, g.file_path, g.status, s.control_number
    FROM grammarly_ai g
    JOIN students s ON g.student_id = s.id
    WHERE g.id = ?
");
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<p class='text-red-500'>Submission not found.</p>";
    exit();
}

$relativePath = "/RSSMS/uploads/" . $submission['file_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

$currentStatus = $submission['status'] ?? 'Pending';

// Get comment count
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total_comments
    FROM grammarly_ai_comments
    WHERE grammarly_ai_id = ?
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

                <span id="commentHeaderCount" class="text-blue-700 text-xs">
                    <?php if (!$viewOnly): ?>You Currently Added<?php endif; ?> <?= $totalComments ?> Comment<?= $totalComments != 1 ? 's' : '' ?>
                </span>
            </div>
            <?php if (!$viewOnly): ?>
                <div class="flex gap-3">
                    <button
                        class="btn-approve px-5 py-2 text-xs font-medium transition
                    <?= $currentStatus === 'Approved'
                        ? 'text-gray-500 cursor-not-allowed'
                        : 'text-blue-700 hover:underline' ?>"
                        data-id="<?= $submission['id'] ?>">
                        Approve
                    </button>
                    <span>-</span>
                    <button
                        class="btn-reject px-5 py-2 text-xs font-medium transition
                    <?= $currentStatus === 'Rejected'
                        ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                        : 'text-red-700 hover:underline' ?>"
                        data-id="<?= $submission['id'] ?>">
                        Reject
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div>
        <p class="text-gray-400 text-xs uppercase tracking-wide mb-3">
            Submission File
        </p>

        <?php if (file_exists($absolutePath)): ?>
            <div class="rounded-xl overflow-hidden border shadow-sm">
                <iframe src="<?= $relativePath ?>"
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

    <div class="flex justify-between pt-6 border-t">

        <div>
            <button
                id="viewCommentBtn"
                onclick="openViewCommentModal(<?= $submission['id'] ?>)"
                <?= $totalComments == 0 ? 'disabled' : '' ?>
                class="px-6 py-2.5 rounded-lg text-sm font-medium shadow transition
        <?= $totalComments == 0
            ? 'bg-gray-300 text-gray-500 opacity-50 cursor-not-allowed'
            : 'bg-blue-600 text-white hover:bg-blue-700' ?>">
                View Comment
            </button>
        </div>
        <?php if (!$viewOnly): ?>
            <div>
                <button
                    onclick="openCommentModal(<?= $submission['id'] ?>)"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-900 transition">
                    Add Comment
                </button>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ADD COMMENT MODAL -->
<div id="commentModal"
    class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center z-50">

    <div class="bg-white w-[500px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4">

        <h3 class="text-lg font-semibold text-gray-800">
            Add Comment
        </h3>

        <div id="commentError" class="hidden text-red-600 text-sm mt-2">
            Please complete all fields.
        </div>

        <div id="commentCounter" class="text-sm text-gray-500">
            Comment No. <span><?= $totalComments ?></span>
        </div>

        <div>
            <label class="text-sm text-gray-600">Page Number</label>
            <input type="number" id="commentPage"
                class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="text-sm text-gray-600">Paragraph Number</label>
            <input type="number" id="commentParagraph"
                class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="text-sm text-gray-600">Comment</label>
            <textarea id="commentText"
                class="w-full mt-1 border rounded-lg px-3 py-2 text-sm"
                rows="4"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-3">

            <button onclick="closeCommentModal()"
                class="px-4 py-2 text-sm text-gray-600 hover:underline">
                Cancel
            </button>

            <button id="saveCommentBtn"
                class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm">
                Done
            </button>
        </div>

    </div>
</div>

<!-- VIEW COMMENT MODAL -->
<div id="viewCommentModal"
    class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center z-50">

    <div class="bg-white w-[600px] max-w-[95%] rounded-xl shadow-xl p-6 space-y-4">

        <h3 class="text-lg font-semibold text-gray-800">
            Submitted Comments
        </h3>

        <div id="viewCommentList" class="max-h-[400px] overflow-y-auto space-y-3 text-sm">
            <!-- Comments will load here -->
        </div>

        <div class="flex justify-end pt-3">
            <button onclick="closeViewCommentModal()"
                class="bg-gray-800 text-white px-5 py-2 rounded-lg text-sm">
                Close
            </button>
        </div>

    </div>
</div>
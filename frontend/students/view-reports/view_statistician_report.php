<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

// Get submission, verify it belongs to this specific student, and get the personnel name
$stmt = $conn->prepare("
    SELECT c.*, s.control_number, p.full_name AS personnel_name
    FROM statistician c
    JOIN students s ON c.student_id = s.id
    LEFT JOIN personnel p ON c.personnel_id = p.id
    WHERE c.id = ? AND s.user_id = ?
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg'>Submission not found or unauthorized access.</div>";
    exit();
}

$relativePath = "/RSSMS/uploads/statistician/" . $submission['file_path'];
$absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

// Fetch Comments from personnel
$stmtComments = $conn->prepare("
    SELECT * FROM statistician_comments
    WHERE statistician_id = ?
    ORDER BY created_at ASC
");
$stmtComments->bind_param("i", $submissionId);
$stmtComments->execute();
$comments = $stmtComments->get_result();

$statusColor = "text-gray-600";
if ($submission['status'] === "Approved") $statusColor = "text-green-600";
if ($submission['status'] === "Rejected") $statusColor = "text-red-600";

?>
<div class="max-w-7xl mx-auto space-y-6">

    <div class="bg-white rounded-lg shadow p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Statistician Report Review</h2>
            <div class="flex items-center gap-4 mt-2 text-sm">
                <p class="text-gray-500">Control No: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($submission['control_number']); ?></span></p>
                <p class="text-gray-500">Round: <span class="font-semibold text-gray-700"><?php echo $submission['round']; ?></span></p>
                <p class="text-gray-500">Status: <span class="font-semibold <?php echo $statusColor; ?>"><?php echo $submission['status']; ?></span></p>
                <p class="text-gray-500">Personnel: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($submission['personnel_name'] ?? ''); ?></span></p>
            </div>
        </div>
        <div>
            <a href="student_dashboard.php?page=students_rs_statistician"
                class="bg-gray-800 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow hover:bg-gray-900 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Submitted Document</h3>

            <?php if (file_exists($absolutePath)): ?>
                <div class="rounded-xl overflow-hidden border shadow-sm">
                    <iframe src="<?php echo $relativePath; ?>" class="w-full h-[700px] bg-gray-50" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    File not found or has been moved.
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col h-[775px]">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-3 border-b">Personnel Comments</h3>

            <div class="flex-1 overflow-y-auto space-y-5 pr-3 custom-scrollbar">
                <?php if ($comments->num_rows > 0): ?>
                    <?php $commentNum = 1; ?>
                    <?php while ($comment = $comments->fetch_assoc()): ?>
                        <div class="bg-gray-50 border border-gray-200 p-5 rounded-2xl transition hover:shadow-md group">

                            <div class="flex justify-between items-center mb-4">
                                <button
                                    onclick="openCommentModalDetails(this)"
                                    data-num="<?php echo $commentNum; ?>"
                                    data-page="<?php echo htmlspecialchars($comment['page_number']); ?>"
                                    data-paragraph="<?php echo htmlspecialchars($comment['paragraph_number']); ?>"
                                    data-date="<?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>"
                                    data-text="<?php echo htmlspecialchars($comment['comment_text']); ?>"
                                    class="text-xs font-bold text-blue-700 bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                    Comment #<?php echo $commentNum++; ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                    </svg>
                                </button>

                                <span class="text-xs font-medium text-gray-400">
                                    <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>

                            <div class="mb-3 flex gap-3 text-xs text-gray-500">
                                <span class="bg-white px-2.5 py-1 rounded-md border border-gray-200 shadow-sm">
                                    Page <strong class="text-gray-700"><?php echo htmlspecialchars($comment['page_number']); ?></strong>
                                </span>
                                <span class="bg-white px-2.5 py-1 rounded-md border border-gray-200 shadow-sm">
                                    Par. <strong class="text-gray-700"><?php echo htmlspecialchars($comment['paragraph_number']); ?></strong>
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 line-clamp-3 leading-relaxed">
                                <?php echo htmlspecialchars($comment['comment_text']); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-gray-400 mt-16 flex flex-col items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="font-medium text-gray-500">No Comments Yet</p>
                        <p class="text-sm mt-1">The personnel has not added any comments to this submission.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div id="commentDetailModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800" id="modalCommentTitle">Comment Details</h3>
            <button onclick="closeCommentModalDetails()" class="text-gray-400 hover:text-red-500 bg-gray-200 hover:bg-red-100 p-1 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-6 text-sm text-gray-500 border-b border-gray-100 pb-4">
                <div class="flex gap-5">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1">Page</span>
                        <span class="font-bold text-gray-800 text-base" id="modalCommentPage">-</span>
                    </div>
                    <div class="w-px bg-gray-200"></div>
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1">Paragraph</span>
                        <span class="font-bold text-gray-800 text-base" id="modalCommentParagraph">-</span>
                    </div>
                </div>
                <div class="text-right flex flex-col items-end">
                    <span class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1">Date Posted</span>
                    <span class="font-semibold text-gray-700" id="modalCommentDate">-</span>
                </div>
            </div>

            <div>
                <h4 class="text-[10px] uppercase tracking-wider text-blue-600 font-bold mb-3">Personnel Feedback</h4>
                <div class="bg-blue-50/30 p-4 rounded-xl border border-blue-50">
                    <p class="text-gray-700 whitespace-pre-wrap leading-relaxed text-sm" id="modalCommentText"></p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeCommentModalDetails()" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg text-sm font-medium transition shadow">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function openCommentModalDetails(btn) {
        // Read data directly from the button that was clicked
        document.getElementById('modalCommentTitle').innerText = 'Comment #' + btn.getAttribute('data-num');
        document.getElementById('modalCommentPage').innerText = btn.getAttribute('data-page');
        document.getElementById('modalCommentParagraph').innerText = btn.getAttribute('data-paragraph');
        document.getElementById('modalCommentDate').innerText = btn.getAttribute('data-date');
        document.getElementById('modalCommentText').innerText = btn.getAttribute('data-text');

        // Show modal
        const modal = document.getElementById('commentDetailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCommentModalDetails() {
        const modal = document.getElementById('commentDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal if user clicks outside the white box (on the blurred background)
    document.getElementById('commentDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCommentModalDetails();
        }
    });
</script>

<style>
    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
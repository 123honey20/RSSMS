<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'personnel') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../../backend/config/database.php";

// Get personnel info
$user_id = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT service_role, department_id
    FROM personnel 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$personnel = $res->fetch_assoc();

$_SESSION['service_role'] = $personnel['service_role'];
$_SESSION['department_id'] = $personnel['department_id'];

$serviceRole = $personnel['service_role'] ?? 'Personnel';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Personnel Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Invisible Custom Scrollbar for a cleaner look */
        .custom-scrollbar::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800 font-sans antialiased selection:bg-blue-100 selection:text-blue-900">

    <header class="fixed top-0 left-0 w-full h-[72px] bg-blue-900/95 backdrop-blur-md text-white shadow-md border-b-[3px] border-b-[#FFC107] z-50 flex items-center justify-between px-6 transition-all">
        <div class="flex items-center gap-3">
            <img src="../images/smcc logo.png" alt="Logo" class="w-10 h-10 object-contain drop-shadow-sm">
            <div class="hidden sm:block flex-col justify-center">
                <h1 class="text-sm font-bold tracking-wide leading-tight">RESEARCH SUPPORT SERVICES</h1>
                <p class="text-[10px] text-blue-200 uppercase tracking-widest font-semibold">Monitoring System</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-[11px] text-blue-200 font-medium uppercase tracking-wider mb-0.5">Welcome, Personnel</p>
                <p class="text-sm font-bold"><?php echo htmlspecialchars($_SESSION['school_id']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-800 border border-blue-400/50 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </header>

    <div class="flex pt-[72px] min-h-screen">

        <aside class="fixed left-0 top-[72px] w-64 h-[calc(100vh-72px)] bg-white border-r border-gray-200 flex flex-col z-40 overflow-y-auto custom-scrollbar">

            <nav class="flex-1 py-5 flex flex-col gap-4">

                <div class="px-5 mb-2">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Personnel Role</span>
                    <span class="inline-flex items-center justify-center bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-md text-[11px] tracking-wide border border-blue-100">
                        <?php echo htmlspecialchars($serviceRole); ?>
                    </span>
                </div>

                <?php
                $submissionPage = '';
                switch ($serviceRole) {
                    case 'Grammarly & AI Checking':
                        $submissionPage = 'submissions_g_ai';
                        break;
                    case 'Ethics':
                        $submissionPage = 'submissions_ethics';
                        break;
                    case 'Librarian':
                        $submissionPage = 'submissions_librarian';
                        break;
                    case 'Statistician':
                        $submissionPage = 'submissions_statistician';
                        break;
                    case 'Human Grammarian':
                        $submissionPage = 'submissions_human_grammarian';
                        break;
                    default:
                        $submissionPage = 'dashboard';
                        break;
                }
                ?>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Management</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="personnel_dashboard.php?page=<?php echo $submissionPage; ?>" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Student Submissions
                        </a>

                        <?php if ($serviceRole === 'Grammarly & AI Checking'): ?>
                            <a href="personnel_dashboard.php?page=receipt_verification" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M7 3h10a2 2 0 012 2v16l-7-3-7 3V5a2 2 0 012-2z" />
                                </svg>
                                Receipt Verification
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div x-data="{ open: true }">
                    <div class="px-5 flex items-center justify-between mb-1.5 group cursor-pointer" @click="open = !open">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Engage</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open" class="flex flex-col gap-0.5 px-3" x-cloak>
                        <a href="personnel_dashboard.php?page=personnel_chat" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] text-gray-600 font-medium hover:bg-blue-50 hover:text-blue-700 transition-colors group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m9-2a9 9 0 11-4.5-7.8L21 3l-1.2 6.5A8.96 8.96 0 0117 12z" />
                                </svg>
                            <span>Communicate</span>
                        </a>
                        <a href="personnel_dashboard.php?page=personnel_feedback" class="flex items-center gap-3 px-2.5 py-1.5 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l2.036 6.26a1 1 0 00.95.69h6.58c.969 0 1.371 1.24.588 1.81l-5.326 3.87a1 1 0 00-.364 1.118l2.036 6.26c.3.921-.755 1.688-1.54 1.118l-5.326-3.87a1 1 0 00-1.176 0l-5.326 3.87c-.784.57-1.838-.197-1.539-1.118l2.036-6.26a1 1 0 00-.364-1.118L2.845 11.687c-.783-.57-.38-1.81.588-1.81h6.58a1 1 0 00.95-.69l2.036-6.26z" />
                            </svg>
                            Rating and Feedback
                        </a>
                    </div>
                </div>

            </nav>

            <div class="p-4 border-t border-gray-100 mt-auto flex flex-col gap-1">
                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>

                <a href="../auth/logout.php" class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-red-600 hover:bg-red-50 transition-colors mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2-2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <main class="flex-1 ml-64 p-6 lg:p-8 min-h-[calc(100vh-72px)]">
            <div id="main-content" class="max-w-7xl mx-auto">

                <?php
                $page = $_GET['page'] ?? 'dashboard';

                switch ($page) {

                    case 'submissions_g_ai':
                        include "../personnel/personnel-research-services/submissions_g_ai.php";
                        break;

                    case 'submissions_ethics':
                        include "../personnel/personnel-research-services/submissions_ethics.php";
                        break;

                    case 'submissions_statistician':
                        include "../personnel/personnel-research-services/submissions_statistician.php";
                        break;

                    case 'submissions_librarian':
                        include "../personnel/personnel-research-services/submissions_librarian.php";
                        break;

                    case 'submissions_human_grammarian':
                        include "../personnel/personnel-research-services/submissions_human_grammarian.php";
                        break;

                    case 'receipt_verification':
                        include "../personnel/personnel-research-services/receipt_verification.php";
                        break;

                    case 'personnel_feedback':
                        include "../feedback/feedback-personnel/view_feedback.php";
                        break;

                    case 'personnel_chat':
                        include "../communicate/personnel-communicate/personnel_chat.php";
                        break;

                    default:
                ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 flex flex-col items-center justify-center text-center h-[50vh]">
                            <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center mb-5 border border-blue-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2 tracking-tight">Personnel Dashboard</h2>
                            <p class="text-[13px] text-gray-500 max-w-md leading-relaxed">Select an option from the sidebar to review student submissions, process documents, or view your performance evaluations.</p>
                        </div>
                <?php
                        break;
                }

                ?>

            </div>
        </main>

    </div>

    <div id="toastContainer"
        class="fixed top-6 right-6 space-y-3 z-[9999]"></div>


    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        const serviceMap = {
            "Librarian": "librarian",
            "Human Grammarian": "human_grammarian",
            "Grammarly & AI Checking": "grammarly_ai",
            "Statistician": "statistician",
            "Ethics": "ethics"
        };

        const serviceType = serviceMap["<?php echo $serviceRole; ?>"];

        function loadContent(url) {
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                })
                .catch(err => {
                    console.error('Error loading content:', err);
                });
        }

        document.querySelectorAll('[data-load]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadContent(this.getAttribute('data-load'));
            });
        });

        window.openProfileStudent = function(data) {
            document.getElementById("sp_research_leader").textContent = data.research_leader || "-";
            document.getElementById("sp_control_number").textContent = data.control_number || "-";
            document.getElementById("sp_email").textContent = data.email || "-";
            document.getElementById("sp_department").textContent = data.department_name || "-";
            document.getElementById("sp_course").textContent = data.course_name || "-";

            const modal = document.getElementById("profileModalStudent");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        window.closeProfileStudent = function() {
            const modal = document.getElementById("profileModalStudent");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function loadProcess(submissionId) {
            const url = `../personnel/personnel-access-file/process_${serviceType}.php?id=${submissionId}`;
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                })
                .catch(err => console.error('Error loading submission:', err));
        }

        function viewSubmissionWithComments(submissionId) {

            const url = `../personnel/personnel-access-file/process_${serviceType}.php?id=${submissionId}&viewOnly=1`;

            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;

                    // After loading, automatically open the comment modal
                    setTimeout(() => {
                        openViewCommentModal(submissionId);
                    }, 300);
                })
                .catch(err => console.error('Error loading submission:', err));
        }


        function updateSubmissionStatus(submissionId, status) {
            fetch(`../../backend/ajax/access_file_${serviceType}.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: submissionId,
                        status: status
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Submission ${status}!`, "success");
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast("Failed to update status.", "error");
                    }
                })
                .catch(err => console.error(err));
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-approve')) {
                const id = e.target.dataset.id;
                updateSubmissionStatus(id, 'Approved');
            } else if (e.target.classList.contains('btn-reject')) {
                const id = e.target.dataset.id;
                updateSubmissionStatus(id, 'Rejected');
            }
        });

        // For Adding Comment this is Global for personnel
        let currentSubmissionId = null;
        let commentCount = 0;

        function openCommentModal(submissionId) {

            currentSubmissionId = submissionId;
            // Always sync from DOM before opening
            const hiddenCount = document.getElementById('initialCommentCount');
            if (hiddenCount) {
                commentCount = parseInt(hiddenCount.value) || 0;
            }

            const modal = document.getElementById('commentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.getElementById('commentCounter').innerText =
                "Comment No." + (commentCount + 1);
        }



        function closeCommentModal() {
            const modal = document.getElementById('commentModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.getElementById('commentText').value = '';
            document.getElementById('commentPage').value = '';
            document.getElementById('commentParagraph').value = '';
        }

        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'saveCommentBtn') {

                const page = document.getElementById('commentPage').value;
                const paragraph = document.getElementById('commentParagraph').value;
                const text = document.getElementById('commentText').value;
                const errorDiv = document.getElementById('commentError');

                if (!page || !paragraph || !text) {
                    errorDiv.classList.remove('hidden');
                    setTimeout(() => {
                        errorDiv.classList.add('hidden');
                    }, 1500);
                    return;
                }
                fetch(`../../backend/ajax/save_${serviceType}_comment.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            [`${serviceType}_id`]: currentSubmissionId,
                            page_number: page,
                            paragraph_number: paragraph,
                            comment_text: text
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            commentCount++;
                            // Update hidden field too
                            const hiddenCount = document.getElementById('initialCommentCount');
                            if (hiddenCount) {
                                hiddenCount.value = commentCount;
                            }
                            // Update header text live
                            const header = document.getElementById('commentHeaderCount');
                            if (header) {
                                header.innerText =
                                    "You Currently Added " + commentCount +
                                    (commentCount === 1 ? " Comment" : " Comments");
                            }
                            // Enable View Comment button
                            const viewBtn = document.getElementById('viewCommentBtn');
                            if (viewBtn) {
                                viewBtn.disabled = false;
                                // Remove ALL gray styles
                                viewBtn.classList.remove(
                                    'bg-gray-300',
                                    'text-gray-500',
                                    'opacity-50',
                                    'hidden'
                                );
                                // Add active styles
                                viewBtn.classList.add(
                                    'bg-blue-600',
                                    'text-white',
                                    'hover:bg-blue-700'
                                );
                            }
                            showToast("Comment added successfully!", "success");
                            closeCommentModal();
                        } else {
                            showToast("Failed to save comment.", "error");
                        }
                    })
                    .catch(err => console.error(err));
            }
        });

        // View Comment
        function openViewCommentModal(submissionId) {
            const modal = document.getElementById('viewCommentModal');
            const container = document.getElementById('viewCommentList');
            container.innerHTML = "Loading...";

            fetch(`../../backend/ajax/get_${serviceType}_comments.php?id=` + submissionId)
                .then(res => res.json())
                .then(data => {

                    if (data.length === 0) {
                        container.innerHTML = "<p class='text-gray-500'>No comments found.</p>";
                    } else {
                        container.innerHTML = "";
                        data.forEach((comment, index) => {
                            container.innerHTML += `
                        <div class="border p-3 rounded-lg bg-gray-50">
                            <div class="font-semibold text-gray-700">
                                Comment ${index + 1}:   Page ${comment.page_number}  -  Paragraph ${comment.paragraph_number}
                            </div>
                            <div class="text-gray-600 mt-1">
                                ${comment.comment_text}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                ${comment.created_at}
                            </div>
                        </div>
                    `;
                        });
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
        }

        function closeViewCommentModal() {
            const modal = document.getElementById('viewCommentModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }


        function showToast(message, type = "success") {

            const container = document.getElementById("toastContainer");

            const toast = document.createElement("div");

            const baseClasses =
                "flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transform transition-all duration-300 translate-x-full opacity-0";

            const typeClasses = type === "success" ?
                "bg-green-600 text-white" :
                "bg-red-600 text-white";

            toast.className = `${baseClasses} ${typeClasses}`;
            toast.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    ${
                        type === "success"
                        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7"/>`
                        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"/>`
                    }
                </svg>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            // Trigger animation
            setTimeout(() => {
                toast.classList.remove("translate-x-full", "opacity-0");
            }, 50);

            // Auto remove Notif
            setTimeout(() => {
                toast.classList.add("translate-x-full", "opacity-0");
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // For Updating the Status of Receipt
        function updateReceiptStatus(id, status) {
            fetch(`../../backend/ajax/update_receipt_verification.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        status: status
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Using your existing custom toast notifications!
                        showToast(`Receipt ${status}!`, "success");
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast("Failed to update receipt status.", "error");
                    }
                })
                .catch(err => console.error(err));
        }
    </script>

</body>

</html>
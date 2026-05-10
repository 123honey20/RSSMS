<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$reqQuery = $conn->query("
    SELECT sa.id as app_id, sa.service_type, s.control_number, s.research_leader, u.email
    FROM service_applications sa
    JOIN students s ON sa.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sa.round_request_status = 'Pending'
");

// Helper function for service badge colors
function getServiceBadgeStyle($service)
{
    $styles = [
        'Grammarly & AI Checking' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50',
        'Ethics'                  => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800/50',
        'Human Grammarian'        => 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/50',
        'Librarian'               => 'bg-pink-50 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400 border-pink-200 dark:border-pink-800/50',
        'Statistician'            => 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800/50'
    ];
    return $styles[$service] ?? 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700';
}
?>

<div class="space-y-6 transition-colors duration-200">

    <!-- Toast Notifications -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div id="toast-success" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-900/30 text-emerald-800 dark:text-emerald-400 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3 mb-4 transition-colors">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium text-sm"><?= $_SESSION['flash_success']; ?></span>
        </div>
        <script>
            setTimeout(() => document.getElementById('toast-success')?.remove(), 4000);
        </script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div id="toast-error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-400 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3 mb-4 transition-colors">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-medium text-sm"><?= $_SESSION['flash_error']; ?></span>
        </div>
        <script>
            setTimeout(() => document.getElementById('toast-error')?.remove(), 4000);
        </script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-gray-200 dark:border-warmdark-border">
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pending Round Extensions
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-8">Review and manage student requests for additional document submission rounds.</p>
        </div>
        <div class="shrink-0 bg-blue-50 dark:bg-warmdark-bg text-blue-800 dark:text-blue-400 px-4 py-2 rounded-lg border border-blue-100 dark:border-warmdark-border shadow-sm text-sm font-bold flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            <?= $reqQuery->num_rows ?> Pending Request(s)
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">

        <?php if ($reqQuery->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50/80 dark:bg-warmdark-bg/80 text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border">
                        <tr>
                            <th class="px-6 py-4">Student & Control No.</th>
                            <th class="px-6 py-4">Requested Service</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border">
                        <?php while ($row = $reqQuery->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors group">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white text-base leading-none mb-1">
                                                <?= htmlspecialchars($row['control_number']) ?>
                                            </p>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Leader: <?= htmlspecialchars($row['research_leader']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border <?= getServiceBadgeStyle($row['service_type']) ?>">
                                        <?= htmlspecialchars($row['service_type']) ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <form action="../../backend/actions/admin/process_round_request.php" method="POST" class="flex justify-end items-center gap-3">
                                        <input type="hidden" name="app_id" value="<?= $row['app_id'] ?>">
                                        <input type="hidden" name="email" value="<?= $row['email'] ?>">

                                        <!-- Styled Number Input -->
                                        <div class="flex items-center border border-gray-300 dark:border-warmdark-border rounded-lg overflow-hidden shadow-sm bg-white dark:bg-warmdark-bg focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
                                            <div class="px-3 text-[10px] font-bold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-warmdark-hover border-r border-gray-200 dark:border-warmdark-border h-full flex items-center uppercase tracking-wider">
                                                Add Rounds
                                            </div>
                                            <input type="number" name="extra_rounds" value="1" min="1" max="5" class="w-14 px-2 py-2 text-sm text-center font-bold text-gray-900 dark:text-white bg-transparent focus:outline-none appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>

                                        <button type="button" onclick="triggerConfirm(this.form, 'Approve')" class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm hover:shadow">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>

                                        <button type="button" onclick="triggerConfirm(this.form, 'Reject')" class="flex items-center gap-1.5 bg-white dark:bg-warmdark-panel border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Beautiful Empty State -->
            <div class="py-16 px-6 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-50 dark:bg-warmdark-bg border border-gray-100 dark:border-warmdark-border rounded-full flex items-center justify-center mb-4 shadow-sm">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">No Pending Requests</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">All student round extension requests have been processed. You're all caught up!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- CUSTOM CONFIRM MODAL -->
<div id="customConfirmModal" class="fixed inset-0 z-[99998] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all p-6 text-center border border-transparent dark:border-warmdark-border">
        <div id="confirmIconContainer" class="mx-auto flex items-center justify-center h-14 w-14 rounded-full mb-4">
            <!-- Icon injected via JS -->
        </div>
        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white mb-2" id="confirmTitle">Are you sure?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8" id="confirmMessage">You are about to process this request.</p>
        <div class="flex items-center gap-3 w-full">
            <button type="button" onclick="closeConfirmModal()" class="w-1/2 bg-gray-100 dark:bg-warmdark-hover text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-warmdark-border py-2.5 rounded-xl font-bold text-sm transition-colors border border-gray-200 dark:border-warmdark-border">Cancel</button>
            <button type="button" id="confirmBtn" class="w-1/2 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition-colors">Confirm</button>
        </div>
    </div>
</div>

<!-- FULL SCREEN PROCESSING OVERLAY -->
<div id="processingOverlay" class="fixed inset-0 z-[99999] bg-black/60 hidden items-center justify-center backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel p-8 rounded-2xl flex flex-col items-center shadow-2xl border border-transparent dark:border-warmdark-border transform scale-100 animate-pulse">
        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" id="processingText">Processing Request...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Please wait while we update the database and notify the student.</p>
    </div>
</div>

<script>
    let pendingForm = null;
    let pendingAction = null;

    function triggerConfirm(form, action) {
        pendingForm = form;
        pendingAction = action;

        const modal = document.getElementById('customConfirmModal');
        const title = document.getElementById('confirmTitle');
        const message = document.getElementById('confirmMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        const iconContainer = document.getElementById('confirmIconContainer');

        if (action === 'Approve') {
            title.innerText = 'Approve Extension?';
            message.innerText = 'This will grant the student the requested extra rounds. Are you sure you want to proceed?';
            confirmBtn.className = 'w-1/2 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition-colors bg-emerald-600 hover:bg-emerald-700 border border-emerald-700';
            confirmBtn.innerText = 'Yes, Approve';
            iconContainer.className = 'mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 mb-4 border border-emerald-200 dark:border-emerald-800/50';
            iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>';
        } else {
            title.innerText = 'Reject Extension?';
            message.innerText = 'This will deny the request. The student will not be able to upload. Proceed?';
            confirmBtn.className = 'w-1/2 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition-colors bg-red-600 hover:bg-red-700 border border-red-700';
            confirmBtn.innerText = 'Yes, Reject';
            iconContainer.className = 'mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-4 border border-red-200 dark:border-red-800/50';
            iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeConfirmModal() {
        const modal = document.getElementById('customConfirmModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingForm = null;
        pendingAction = null;
    }

    document.getElementById('confirmBtn').addEventListener('click', function() {
        if (pendingForm && pendingAction) {
            // FIX: Capture the form and action BEFORE closing the modal!
            const formToSubmit = pendingForm;
            const actionToSubmit = pendingAction;
            
            closeConfirmModal();
            
            // Show processing overlay with the correct text
            document.getElementById('processingText').innerText = actionToSubmit === 'Approve' ? 'Approving Request...' : 'Rejecting Request...';
            document.getElementById('processingOverlay').classList.remove('hidden');
            document.getElementById('processingOverlay').classList.add('flex');
            
            // Inject the button action value dynamically into the form
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = actionToSubmit;
            formToSubmit.appendChild(actionInput);
            
            // Submit the form natively
            formToSubmit.submit();
        }
    });
</script>
<?php
require_once "../../backend/config/database.php";

// Fetch Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

$start_year = 2024;
$current_calendar_year = (int)date("Y");
$max_year = $current_calendar_year + 2;

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border min-h-[80vh] transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Student Feedback & Ratings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review evaluations submitted by students for research personnel.</p>
        </div>
        <a href="admin_dashboard.php?page=feedback_admin_view"
            class="bg-blue-900 dark:bg-blue-800 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition shadow-sm font-medium">
            Manage Rubrics
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <input type="text" id="feedbackSearch" placeholder="Search Control No. or Personnel..."
            class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm transition-colors">
        
        <select id="syFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm transition-colors">
            <option value="All">All School Years</option>
            <?php foreach ($generated_years as $year): ?>
                <option value="<?php echo $year; ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?php echo $year; ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="serviceFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 font-medium focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm transition-colors">
            <option value="All">All Services</option>
            <option value="grammarly_ai">Grammarly & AI</option>
            <option value="ethics">Ethics</option>
            <option value="human_grammarian">Human Grammarian</option>
            <option value="librarian">Librarian</option>
            <option value="statistician">Statistician</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left border-collapse text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">Control No.</th>
                    <th class="px-6 py-4 font-semibold">Personnel Evaluated</th>
                    <th class="px-6 py-4 font-semibold text-center">Date Submitted</th>
                    <th class="px-6 py-4 font-semibold text-center">Feedback</th>
                    <th class="px-6 py-4 font-semibold text-center">Profile</th>
                </tr>
            </thead>
            <tbody id="feedbackTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="profileModalStudent" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[9999] p-4 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-transparent dark:border-warmdark-border">
        <div class="bg-blue-900 dark:bg-warmdark-bg text-white px-6 py-4 flex items-center justify-between shrink-0 dark:border-b dark:border-warmdark-border">
            <h3 class="text-lg font-bold tracking-wide">Student Profile</h3>
            <button onclick="closeProfileStudent()" class="text-blue-200 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800 dark:text-gray-200">

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School ID</p>
                    <p class="font-bold text-gray-900 dark:text-gray-100" id="p_school_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email</p>
                    <p class="font-medium text-gray-800 dark:text-gray-300" id="p_email"></p>
                </div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                    <p class="font-medium text-gray-800 dark:text-gray-300" id="p_department_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Course</p>
                    <p class="font-medium text-gray-800 dark:text-gray-300" id="p_course_id"></p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 dark:border-warmdark-border my-1"></div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Control Number</p>
                    <span class="font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-0.5 rounded border border-blue-100 dark:border-blue-900/50 inline-block" id="p_control_number"></span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Research Leader</p>
                    <p class="font-medium text-gray-800 dark:text-gray-300" id="p_research_leader"></p>
                </div>

                <div class="md:col-span-2 bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border mt-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Thesis Title</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 leading-snug" id="p_thesis_title"></p>
                </div>

                <span id="p_status" style="display: none;"></span>

            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeProfileStudent()" class="bg-white dark:bg-warmdark-hover border border-gray-300 dark:border-warmdark-border px-6 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-warmdark-border text-gray-700 dark:text-gray-200 text-sm font-bold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="feedbackDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[9999] p-4 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-transparent dark:border-warmdark-border">
        <div class="bg-gray-50 dark:bg-warmdark-bg border-b border-gray-200 dark:border-warmdark-border px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Evaluation Details</h3>
            <button onclick="closeFeedbackModal()" class="text-gray-400 hover:text-red-500 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-900/30">
                <div>
                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Personnel Evaluated</p>
                    <p class="font-bold text-gray-900 dark:text-gray-100 text-lg" id="f_personnel_name"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400" id="f_service_role"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Score</p>
                    <span class="text-2xl font-black text-blue-700 dark:text-blue-400" id="f_total_score"></span>
                    <span class="text-gray-500 dark:text-gray-400 font-bold">pts</span>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Instrument Used</p>
                <p class="font-semibold text-gray-700 dark:text-gray-200" id="f_rubric_name"></p>
            </div>

            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Student's Additional Comments</p>
                <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap italic" id="f_comments"></div>
            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeFeedbackModal()" class="bg-blue-900 dark:bg-blue-800 text-white px-6 py-2.5 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 text-sm font-bold transition shadow-sm">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchFeedback(page = 1) {
        currentPage = page;
        const search = document.getElementById('feedbackSearch').value;
        const sy = document.getElementById('syFilter').value;
        const service = document.getElementById('serviceFilter').value;

        fetch(`../../backend/ajax/fetch_student_evaluations.php?p=${page}&search=${encodeURIComponent(search)}&sy=${encodeURIComponent(sy)}&service=${encodeURIComponent(service)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert("Database Error: " + data.error);
                    console.error("Backend Error:", data.error);
                    return;
                }

                const tbody = document.getElementById('feedbackTableBody');
                tbody.innerHTML = '';

                if (data.evaluations.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No feedback found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.evaluations.forEach(eval => {
                    let formattedDate = "Unknown Date";
                    if (eval.created_at) {
                        const dateObj = new Date(eval.created_at);
                        formattedDate = dateObj.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                    }

                    // Format the service type string for display
                    let displayService = eval.service_type || 'Service';
                    if(displayService === 'grammarly_ai') displayService = 'Grammarly & AI';
                    else if(displayService === 'human_grammarian') displayService = 'Human Grammarian';
                    else if(displayService === 'ethics') displayService = 'Ethics';
                    else if(displayService === 'librarian') displayService = 'Librarian';
                    else if(displayService === 'statistician') displayService = 'Statistician';

                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200">${eval.control_number || 'N/A'}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">${eval.personnel_name || 'Unknown'}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">${eval.service_role || displayService}</div>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 text-xs">${formattedDate}</td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 dark:text-blue-400 px-4 py-1.5 text-xs hover:underline"
                                onclick='openFeedbackModal(${JSON.stringify(eval).replace(/'/g, "&#39;")})'>
                                View Score
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 dark:text-blue-400 px-4 py-1.5 text-xs hover:underline"
                                onclick='openProfileStudent(${JSON.stringify(eval).replace(/'/g, "&#39;")})'>
                                Student Info
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);

                const totalRows = data.totalRows || 0;
                const startRecord = (totalRows > 0) ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} records` : '';
            })
            .catch(error => console.error('Fetch error:', error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchFeedback(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchFeedback(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchFeedback(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    function openFeedbackModal(data) {
        document.getElementById("f_personnel_name").textContent = data.personnel_name || "Unknown";
        
        let displayService = data.service_role || data.service_type || "Research Services";
        if(displayService === 'grammarly_ai') displayService = 'Grammarly & AI';
        else if(displayService === 'human_grammarian') displayService = 'Human Grammarian';
        
        document.getElementById("f_service_role").textContent = displayService;
        document.getElementById("f_total_score").textContent = data.total_score || "0";
        document.getElementById("f_rubric_name").textContent = data.rubric_name || "Unknown Instrument";
        document.getElementById("f_comments").textContent = data.comments ? data.comments : "No additional comments provided by the student.";

        const modal = document.getElementById("feedbackDetailModal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function closeFeedbackModal() {
        const modal = document.getElementById("feedbackDetailModal");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    document.getElementById('syFilter').addEventListener('change', () => fetchFeedback(1));
    document.getElementById('serviceFilter').addEventListener('change', () => fetchFeedback(1));

    document.getElementById('feedbackSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchFeedback(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchFeedback(1));
</script>
<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Student Feedback & Ratings</h2>
            <p class="text-sm text-gray-500">Review evaluations submitted by students for research personnel.</p>
        </div>
        <a href="admin_dashboard.php?page=feedback_admin_view"
            class="bg-blue-900 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-blue-800 transition shadow-sm font-medium">
            Manage Rubrics
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input type="text" id="feedbackSearch" placeholder="Search by Control No..."
            class="w-full md:w-1/3 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm">
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left border-collapse text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">Control No.</th>
                    <th class="px-6 py-4 font-semibold">Personnel Evaluated</th>
                    <th class="px-6 py-4 font-semibold text-center">Date Submitted</th>
                    <th class="px-6 py-4 font-semibold text-center">Feedback</th>
                    <th class="px-6 py-4 font-semibold text-center">Profile</th>
                </tr>
            </thead>
            <tbody id="feedbackTableBody" class="divide-y divide-gray-100"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="profileModalStudent" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="text-lg font-bold">Student Profile</h3>
            <button onclick="closeProfileStudent()" class="text-blue-200 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800">

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School ID</p>
                    <p class="font-bold text-gray-900" id="p_school_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email</p>
                    <p class="font-medium text-gray-800" id="p_email"></p>
                </div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                    <p class="font-medium text-gray-800" id="p_department_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Course</p>
                    <p class="font-medium text-gray-800" id="p_course_id"></p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 my-1"></div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Control Number</p>
                    <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block" id="p_control_number"></span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Research Leader</p>
                    <p class="font-medium text-gray-800" id="p_research_leader"></p>
                </div>

                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200 mt-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Thesis Title</p>
                    <p class="font-semibold text-gray-900 leading-snug" id="p_thesis_title"></p>
                </div>

                <span id="p_status" style="display: none;"></span>

            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeProfileStudent()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="feedbackDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between shrink-0">
            <h3 class="text-lg font-bold text-gray-800">Evaluation Details</h3>
            <button onclick="closeFeedbackModal()" class="text-gray-400 hover:text-red-500 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                <div>
                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Personnel Evaluated</p>
                    <p class="font-bold text-gray-900 text-lg" id="f_personnel_name"></p>
                    <p class="text-sm text-gray-500" id="f_service_role"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Score</p>
                    <span class="text-2xl font-black text-blue-700" id="f_total_score"></span>
                    <span class="text-gray-500 font-bold">pts</span>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Instrument Used</p>
                <p class="font-semibold text-gray-700" id="f_rubric_name"></p>
            </div>

            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Student's Additional Comments</p>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap italic" id="f_comments"></div>
            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeFeedbackModal()" class="bg-blue-900 text-white px-6 py-2.5 rounded-lg hover:bg-blue-800 text-sm font-bold transition shadow-sm">Done</button>
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

        fetch(`../../backend/ajax/fetch_student_evaluations.php?p=${page}&search=${encodeURIComponent(search)}`)
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
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="font-medium text-gray-500">No feedback found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
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

                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 transition";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800">${eval.control_number || 'N/A'}</td>
                        <td class="px-6 py-4 text-gray-700">
                            <div class="font-semibold">${eval.personnel_name || 'Unknown'}</div>
                            <div class="text-[10px] text-gray-500 uppercase tracking-wider">${eval.service_role || 'Service'}</div>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 text-xs">${formattedDate}</td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 px-4 py-1.5 text-xs hover:underline"
                                onclick='openFeedbackModal(${JSON.stringify(eval).replace(/'/g, "&#39;")})'>
                                View Score
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 px-4 py-1.5 text-xs hover:underline"
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
            container.innerHTML += `<button onclick="fetchFeedback(${currentPage - 1})" class="px-3 py-1 border border-gray-200 rounded-md text-xs text-gray-600 hover:bg-gray-50 transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchFeedback(${i})" class="px-3 py-1 text-xs border border-gray-200 rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 hover:bg-gray-50'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchFeedback(${currentPage + 1})" class="px-3 py-1 border border-gray-200 text-gray-600 text-xs rounded-md hover:bg-gray-50 transition shadow-sm">Next</button>`;
        }
    }

    function openFeedbackModal(data) {
        document.getElementById("f_personnel_name").textContent = data.personnel_name || "Unknown";
        document.getElementById("f_service_role").textContent = data.service_role || "Research Services";
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

    document.getElementById('feedbackSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchFeedback(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchFeedback(1));
</script>
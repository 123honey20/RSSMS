<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Student Submissions</h2>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search by Control Number..."
            class="w-full md:w-1/3 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm">
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left border-collapse text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">No.</th>
                    <th class="px-6 py-4 font-semibold">Control No.</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Round</th>
                    <th class="px-6 py-4 font-semibold text-center">Student Profile</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>
    </div>
</div>

<!-- Student Profile Modal -->
<div id="profileModalStudent"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 ease-out">
        <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-8 py-5 flex items-center justify-between">
            <h3 class="text-xl font-semibold tracking-wide">Student Profile</h3>
            <button onclick="closeProfileStudent()"
                class="text-white hover:text-gray-200 text-2xl font-bold leading-none">
                ✕
            </button>
        </div>

        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800">

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Control Number</p>
                    <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-100 inline-block break-words" id="sp_control_number">-</span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Research Leader</p>
                    <p class="font-medium text-gray-800 break-words" id="sp_research_leader">-</p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Email</p>
                    <p class="font-medium text-gray-800 break-all" id="sp_email">-</p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 my-1"></div>

                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department</p>
                    <p class="font-semibold text-gray-900 break-words leading-snug" id="sp_department">-</p>
                </div>

                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Course</p>
                    <p class="font-semibold text-gray-900 break-words leading-snug" id="sp_course">-</p>
                </div>

            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeProfileStudent()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchSubmissions(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;

        fetch(`../../backend/ajax/fetch_human_grammarian_submissions.php?p=${page}&search=${encodeURIComponent(search)}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if (data.submissions.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-medium text-gray-500">No Submissions Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.submissions.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50/50 transition";

                    let statusBadge = '';
                    if (row.status === 'Approved') {
                        statusBadge = `<span class="text-green-700 font-bold px-3 py-1.5 text-xs">Approved</span>`;
                    } else if (row.status === 'Rejected') {
                        statusBadge = `<span class="text-red-700 font-bold px-3 py-1.5 text-xs">Rejected</span>`;
                    } else {
                        statusBadge = `<span class="text-yellow-700 font-bold px-3 py-1.5 text-xs">Pending</span>`;
                    }

                    let actionButton = '';
                    if (row.status === 'Approved' || row.status === 'Rejected') {
                        actionButton = `<button onclick="viewSubmissionWithComments(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm">Review Submission</button>`;
                    } else {
                        actionButton = `<button onclick="loadProcess(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm">Process</button>`;
                    }

                    tr.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800">${row.control_number}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-center font-medium text-gray-600">${row.round}</td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 px-4 py-1.5 rounded-lg hover:underline text-xs"
                                onclick='openProfileStudent(${JSON.stringify(row).replace(/'/g, "&#39;")})'>
                                View Profile
                            </button>
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            ${actionButton}
                        </td>
                    `;

                    tbody.appendChild(tr);
                });

                renderPagination(data.totalPages, data.currentPage);

                const totalRows = data.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                document.getElementById('recordInfo').textContent =
                    totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Submissions` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchSubmissions(${currentPage - 1})" class="px-3 py-1 border border-gray-200 rounded-md text-xs text-gray-600 hover:bg-gray-50 transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchSubmissions(${i})" class="px-3 py-1 text-xs border border-gray-200 rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 hover:bg-gray-50'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchSubmissions(${currentPage + 1})" class="px-3 py-1 border border-gray-200 text-gray-600 text-xs rounded-md hover:bg-gray-50 transition shadow-sm">Next</button>`;
        }
    }

    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchSubmissions(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchSubmissions(1));
</script>
<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Student Submissions</h2>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input
            type="text"
            id="searchInput"
            placeholder="Search by Control Number"
            class="w-full md:w-1/2 border rounded px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border-b text-xs text-center">No.</th>
                    <th class="p-3 border-b text-xs">Control No.</th>
                    <th class="p-3 border-b text-xs text-center">Status</th>
                    <th class="p-3 border-b text-xs text-center">Round</th>
                    <th class="p-3 border-b text-xs text-center">Student Profile</th>
                    <th class="p-3 border-b text-xs text-center">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-600 text-center"></div>
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

        <div class="p-8 space-y-6 text-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Research Leader</p>
                    <p class="font-medium text-gray-800" id="sp_research_leader">-</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Control Number</p>
                    <p class="font-medium text-gray-800" id="sp_control_number">-</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Email</p>
                    <p class="font-medium text-gray-800" id="sp_email">-</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Department</p>
                    <p class="font-medium text-gray-800" id="sp_department">-</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide">Course</p>
                    <p class="font-medium text-gray-800" id="sp_course">-</p>
                </div>

            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeProfileStudent()"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition-colors font-medium text-sm">
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
                        <td colspan="6" class="text-center p-4 text-gray-500">
                            No Submissions Found.
                        </td>
                    </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.submissions.forEach(row => {

                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50 transition";

                    tr.innerHTML = `
                    <td class="p-3 border-b text-center">${counter++}.</td>

                    <td class="p-3 border-b">${row.control_number}</td>

                    <td class="p-3 border-b text-center">
                        ${
                            row.status === 'Approved' ? `<span class="px-3 py-1 text-xs rounded-full text-green-700 font-semibold">Approved</span>`
                            : row.status === 'Rejected' ? `<span class="px-3 py-1 text-xs rounded-full text-red-700 font-semibold">Rejected</span>`
                            : `<span class="px-3 py-1 text-xs rounded-full text-yellow-700 font-semibold">Pending</span>`
                        }
                    </td>


                    <td class="p-3 border-b text-center">
                        ${row.round}
                    </td>

                    <td class="p-3 border-b text-center">
                        <button class="text-blue-700 hover:underline text-sm"
                            onclick='openProfileStudent(${JSON.stringify(row)})'>
                            View
                        </button>

                    </td>

                    <td class="p-3 border-b text-center">
                        ${
                            row.status === 'Approved'
                            ? `<button onclick="viewSubmissionWithComments(${row.id})" class="text-blue-700 hover:underline text-sm">
                                    Review Submission
                               </button>`
                            : row.status === 'Rejected' ? `<button onclick="viewSubmissionWithComments(${row.id})" class="text-blue-700  hover:underline text-sm">Review Submission</button>`
                            : `<button onclick="loadProcess(${row.id})"
                                    class="text-blue-700 hover:underline text-sm">
                                    Process
                               </button>`
                        }
                    </td>

                `;

                    tbody.appendChild(tr);
                });

                renderPagination(data.totalPages, data.currentPage);

                const totalRows = data.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                document.getElementById('recordInfo').textContent =
                    totalRows > 0 ?
                    `Showing ${startRecord} - ${endRecord} of ${totalRows} Submissions` :
                    '';
            });
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `
            <button onclick="fetchSubmissions(${currentPage - 1})"
                class="px-2 py-1 border rounded text-xs hover:bg-gray-100">
                Prev
            </button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `
            <button onclick="fetchSubmissions(${i})"
                class="px-2 py-1 text-xs border rounded
                ${i === currentPage ? 'bg-blue-900 text-white' : 'hover:bg-gray-100'}">
                ${i}
            </button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `
            <button onclick="fetchSubmissions(${currentPage + 1})"
                class="px-2 py-1 border text-xs rounded hover:bg-gray-100">
                Next
            </button>`;
        }
    }

    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchSubmissions(1);
        }, 400);
    });

    document.addEventListener('DOMContentLoaded', () => {
        fetchSubmissions(1);
    });
</script>
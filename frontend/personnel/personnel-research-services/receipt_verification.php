<?php
if ($_SESSION['service_role'] !== 'Grammarly & AI Checking') {
    die("Access Denied");
}
// Fetch departments for the dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Receipt Verification for Grammarly & AI Checking Service</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search by Control Number..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm">
        
        <select id="deptFilter" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm text-gray-700 font-medium">
            <option value="All">All Departments</option>
            <?php while($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="statusFilter" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm text-gray-700 font-medium">
            <option value="All">All Statuses</option>
            <option value="Receipt Uploaded">Receipt Uploaded (Pending)</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 w-full">
        <table class="w-full min-w-max text-sm text-left border-collapse text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-32">Round</th>
                    <th class="px-6 py-4 font-semibold whitespace-nowrap w-1/3">Control No.</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-40">Status</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-40">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100"></tbody>
        </table>
    </div>

    <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
    <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>

</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchData(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const dept = document.getElementById('deptFilter').value;
        const status = document.getElementById('statusFilter').value;

        fetch(`../../backend/ajax/fetch_receipt_verification.php?p=${page}&search=${encodeURIComponent(search)}&dept=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`)
            .then(res => res.json())
            .then(res => {
                const tbody = document.getElementById("tableBody");
                tbody.innerHTML = "";

                if (!res.data || res.data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-medium text-gray-500">No uploaded receipts found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                res.data.forEach(row => {
                    let actionButton = '';
                    if (row.status === 'Approved' || row.status === 'Rejected') {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm whitespace-nowrap">Receipt Review</button>`;
                    } else {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm whitespace-nowrap">Process</button>`;
                    }

                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-center text-xs text-gray-600 font-bold align-middle whitespace-nowrap">
                                Round ${row.round}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800 align-middle whitespace-nowrap">
                                ${row.control_number}
                            </td>
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                ${getStatusBadge(row.status)}
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2 align-middle">
                                ${actionButton}
                            </td>
                        </tr>
                    `;
                });

                renderPagination(res.totalPages, res.currentPage);

                const totalRows = res.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                document.getElementById('recordInfo').textContent =
                    totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Receipts` : '';
            })
            .catch(error => console.error("Error fetching data:", error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchData(${currentPage - 1})" class="px-3 py-1 border border-gray-200 rounded-md text-xs text-gray-600 hover:bg-gray-50 transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchData(${i})" class="px-3 py-1 text-xs border border-gray-200 rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 text-white border-blue-900' : 'text-gray-600 hover:bg-gray-50'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchData(${currentPage + 1})" class="px-3 py-1 border border-gray-200 text-gray-600 text-xs rounded-md hover:bg-gray-50 transition shadow-sm">Next</button>`;
        }
    }

    function getStatusBadge(status) {
        if (status === 'Approved') {
            return `<span class="text-green-700 bg-green-50 border border-green-100 font-bold px-3 py-1.5 rounded-md text-xs">Approved</span>`;
        }
        if (status === 'Rejected') {
            return `<span class="text-red-700 bg-red-50 border border-red-100 font-bold px-3 py-1.5 rounded-md text-xs">Rejected</span>`;
        }
        if (status === 'Receipt Uploaded') {
            return `<span class="text-yellow-700 bg-yellow-50 border border-yellow-100 font-bold px-3 py-1.5 rounded-md text-xs">Pending Review</span>`;
        }
        return `<span class="bg-gray-50 text-gray-700 font-bold px-3 py-1.5 rounded-md text-xs border border-gray-200">${status}</span>`;
    }

    function loadReceiptProcess(id) {
        const url = `../personnel/personnel-access-file/process_receipt_verification.php?id=${id}`;
        fetch(url)
            .then(res => res.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
            })
            .catch(err => console.error(err));
    }

    // Event Listeners for Filters
    ['deptFilter', 'statusFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchData(1));
    });

    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchData(1), 400);
    });

    document.addEventListener("DOMContentLoaded", () => fetchData(1));
</script>
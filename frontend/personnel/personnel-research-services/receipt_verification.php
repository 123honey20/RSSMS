<?php
if ($_SESSION['service_role'] !== 'Grammarly & AI Checking') {
    die("Access Denied");
}
// Fetch departments for the dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm min-h-[80vh] border border-transparent dark:border-warmdark-border transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Receipt Verification for Grammarly & AI Checking Service</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search by Control Number..."
            class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm text-gray-700 dark:text-gray-200 font-medium transition-colors">
        
        <select id="deptFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm text-gray-700 dark:text-gray-200 font-medium transition-colors">
            <option value="All">All Departments</option>
            <?php while($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="statusFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm text-gray-700 dark:text-gray-200 font-medium transition-colors">
            <option value="All">All Statuses</option>
            <option value="Receipt Uploaded">Receipt Uploaded (Pending)</option>
            <option value="Approved">Approved</option>
            <option value="Needs Revision">Revision</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border w-full transition-colors">
        <table class="w-full min-w-max text-sm text-left border-collapse text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-32">Round</th>
                    <th class="px-6 py-4 font-semibold whitespace-nowrap w-1/3">Control No.</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-40">Status</th>
                    <th class="px-6 py-4 font-semibold text-center whitespace-nowrap w-40">Action</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border transition-colors"></tbody>
        </table>
    </div>

    <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
    <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>

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
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No uploaded receipts found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                res.data.forEach(row => {
                    let actionButton = '';
                    if (row.status === 'Approved' || row.status === 'Needs Revision') {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border px-4 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-warmdark-hover transition font-bold text-xs shadow-sm whitespace-nowrap">Receipt Review</button>`;
                    } else {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border px-4 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-warmdark-hover transition font-bold text-xs shadow-sm whitespace-nowrap">Process</button>`;
                    }

                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors">
                            <td class="px-6 py-4 text-center text-xs text-gray-600 dark:text-gray-300 font-bold align-middle whitespace-nowrap">
                                Round ${row.round}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200 align-middle whitespace-nowrap">
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
            container.innerHTML += `<button onclick="fetchData(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchData(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchData(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    function getStatusBadge(status) {
        if (status === 'Approved') {
            return `<span class="text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/20 border border-green-100 dark:border-green-500/30 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Approved</span>`;
        }
        if (status === 'Needs Revision') {
            return `<span class="text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-900/50 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Needs Revision</span>`;
        }
        if (status === 'Receipt Uploaded') {
            return `<span class="text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-100 dark:border-yellow-900/50 font-bold px-3 py-1.5 rounded-md text-xs transition-colors">Pending Review</span>`;
        }
        return `<span class="bg-gray-50 dark:bg-warmdark-bg text-gray-700 dark:text-gray-400 font-bold px-3 py-1.5 rounded-md text-xs border border-gray-200 dark:border-warmdark-border transition-colors">${status}</span>`;
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
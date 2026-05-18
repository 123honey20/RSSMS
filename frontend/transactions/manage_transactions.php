<?php
// Fetch Departments
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

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

    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-warmdark-border transition-colors">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Student Transactions & Receipts</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monitor all uploaded payment receipts and their verification statuses.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search Control No..."
            class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">

        <select id="syFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">
            <option value="All">All School Years</option>
            <?php foreach ($generated_years as $year): ?>
                <option value="<?php echo $year; ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?php echo $year; ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="deptFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">
            <option value="All">All Departments</option>
            <?php while ($d = $dept_query->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <select id="statusFilter" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2 text-sm bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:outline-none shadow-sm font-medium transition-colors">
            <option value="All">All Statuses</option>
            <option value="No Receipt">No Receipt</option>
            <option value="Receipt Uploaded">Receipt Uploaded</option>
            <option value="Approved">Approved</option>
            <option value="Needs Revision">Needs Revision</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left border-collapse text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">No.</th>
                    <th class="px-6 py-4 font-semibold">Control No.</th>
                    <th class="px-6 py-4 font-semibold">Assigned Personnel</th>
                    <th class="px-6 py-4 font-semibold text-center">Round</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Document</th>
                    <th class="px-6 py-4 font-semibold text-center">Student Profile</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="fileViewerModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[9999] backdrop-blur-sm p-4 sm:p-8 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-4xl h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col transform transition-all border border-transparent dark:border-warmdark-border">
        <div class="bg-gray-900 dark:bg-warmdark-bg text-white px-5 py-3 flex items-center justify-between shrink-0 shadow-md z-10 border-b border-transparent dark:border-warmdark-border">
            <div class="flex items-center gap-3 overflow-hidden">
                <h3 class="text-base font-semibold tracking-wide truncate">Receipt Viewer</h3>
                <span id="viewer-filename" class="text-xs text-gray-400 font-medium truncate hidden sm:block"></span>
            </div>
            <button onclick="closeFileViewer()" class="text-gray-400 hover:text-red-400 hover:bg-gray-800 dark:hover:bg-warmdark-hover rounded-lg p-2 transition-colors focus:outline-none">✕</button>
        </div>
        <div class="flex-1 bg-gray-100 dark:bg-warmdark-bg relative">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 dark:border-t-blue-400 rounded-full animate-spin"></div>
            </div>
            <iframe id="fileViewerIframe" src="" class="w-full h-full border-0 relative z-10 bg-white dark:bg-warmdark-bg"></iframe>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchTransactions(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const dept = document.getElementById('deptFilter').value;
        const status = document.getElementById('statusFilter').value;
        const sy = document.getElementById('syFilter').value;

        fetch(`../../backend/ajax/fetch_admin_transactions.php?p=${page}&search=${encodeURIComponent(search)}&dept=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}&sy=${encodeURIComponent(sy)}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if (data.transactions.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">No transactions found.</td></tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.transactions.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    let badgeColor = "bg-gray-100 dark:bg-warmdark-bg text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-warmdark-border"; 
                    if (row.status === 'Receipt Uploaded') badgeColor = "bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30";
                    if (row.status === 'Approved') badgeColor = "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-900/30";
                    if (row.status === 'Needs Revision') badgeColor = "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/30";

                    let statusBadge = `<span class="inline-flex items-center justify-center w-[120px] px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm ${badgeColor}">${row.status}</span>`;

                    // LOGIC FOR ASSIGNED PERSONNEL
                    let personnelDisplay = row.personnel_name 
                        ? `<div class="font-semibold text-gray-800 dark:text-gray-200">${row.personnel_name}</div>
                           <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grammarly & AI</div>` 
                        : `<span class="text-[11px] text-amber-600 dark:text-amber-500 font-semibold italic bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-900/30">Pending Assignment</span>`;

                    let viewButton = '';
                    if (row.status === 'No Receipt' || !row.receipt_path) {
                        viewButton = `<span class="text-gray-400 dark:text-gray-500 text-[11px] font-medium italic">Not Submitted</span>`;
                    } else {
                        viewButton = `<button onclick="openFileViewer('../../uploads/grammarly_ai/receipts/${row.receipt_path}', '${row.receipt_path}')" 
                                class="text-blue-700 dark:text-blue-400 hover:underline text-xs font-bold inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Receipt
                            </button>`;
                    }

                    tr.innerHTML = `
                        <td class="px-6 py-4 text-center text-xs text-gray-500 dark:text-gray-400">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200">${row.control_number}</td>
                        <td class="px-6 py-4">${personnelDisplay}</td>
                        <td class="px-6 py-4 text-center font-bold text-gray-600 dark:text-gray-300">R${row.round}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-center">
                            ${viewButton}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 dark:text-blue-400 hover:underline text-xs font-bold" 
                                onclick='openProfileStudent(${JSON.stringify(row).replace(/'/g, "&#39;").replace(/"/g, "&quot;")})'>
                                Details
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination(data.totalPages, data.currentPage);
                const totalRows = data.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} Transactions` : '';
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchTransactions(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchTransactions(${i})" class="px-3 py-1 text-xs font-bold border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchTransactions(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs font-bold rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    function openFileViewer(url, filename) {
        document.getElementById('viewer-filename').textContent = "— " + filename;
        document.getElementById('fileViewerIframe').src = url;
        const modal = document.getElementById('fileViewerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeFileViewer() {
        const modal = document.getElementById('fileViewerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        setTimeout(() => { document.getElementById('fileViewerIframe').src = ''; }, 300);
    }

    ['deptFilter', 'statusFilter', 'syFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchTransactions(1));
    });

    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchTransactions(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchTransactions(1));
</script>
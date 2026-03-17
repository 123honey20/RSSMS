<?php
require_once "../../backend/config/database.php";

// Fetch Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// School Year Generation
$start_year = 2024;
$current_calendar_year = (int)date("Y");
$max_year = $current_calendar_year + 2;
$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}

// Fetch All Departments for Dropdown
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = [];
if ($dept_query) {
    while ($row = $dept_query->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Dynamically grab the global template certificate path and type
$cert_dir = "../images/certificates/proposal-certificate/";
$cert_ext = "png";
$cert_type = "image";
if (file_exists($cert_dir . "Proposal_Certificate.pdf")) {
    $cert_ext = "pdf";
    $cert_type = "pdf";
} elseif (file_exists($cert_dir . "Proposal_Certificate.jpg")) {
    $cert_ext = "jpg";
}
$global_cert_url = $cert_dir . "Proposal_Certificate." . $cert_ext;
?>

<div class="bg-white dark:bg-warmdark-panel p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border min-h-[80vh] transition-colors">

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-5 border-b border-gray-100 dark:border-warmdark-border gap-4 transition-colors">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-sm border border-blue-100 dark:border-blue-900/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight">Proposal Clearances</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Track students who have passed or are missing research service requirements.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <input type="text" id="clearanceSearch" placeholder="Search Control No. or Title..."
            class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm transition">

        <select id="statusFilter" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm font-semibold transition">
            <option value="Cleared">Cleared Proposals</option>
            <option value="Uncleared">Uncleared / Pending</option>
        </select>

        <select id="deptFilter" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm font-semibold transition truncate">
            <option value="All">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <select id="syFilter" class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-700 dark:text-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm font-semibold transition">
            <option value="All">All School Years</option>
            <?php foreach ($generated_years as $year): ?>
                <option value="<?php echo $year; ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?php echo $year; ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-warmdark-border shadow-sm transition-colors">
        <table class="w-full text-sm text-left border-collapse text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-bold text-center">#</th>
                    <th class="px-6 py-4 font-bold">Control No.</th>
                    <th class="px-6 py-4 font-bold">Thesis Title</th>
                    <th class="px-6 py-4 font-bold text-center">Clearance Status</th>
                    <th class="px-6 py-4 font-bold text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="clearanceTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-5 flex justify-center gap-2 text-sm pb-5"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-400 dark:text-gray-500 font-medium text-center pb-5 pr-6"></div>
    </div>
</div>

<div id="fileViewerModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[9999] backdrop-blur-sm p-4 sm:p-6 transition-opacity">
    <div class="bg-white dark:bg-warmdark-panel w-full max-w-3xl h-[85vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-transparent dark:border-warmdark-border">
        <div class="bg-gray-900 dark:bg-warmdark-bg dark:border-b dark:border-warmdark-border text-white px-5 py-3 flex items-center justify-between shrink-0 shadow-md z-20">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="bg-green-500/20 p-1.5 rounded-lg border border-green-500/30">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold tracking-wide truncate">Clearance Certificate Viewer</h3>
            </div>
            <button onclick="closeFileViewer()" class="text-gray-400 hover:text-red-400 hover:bg-gray-800 dark:hover:bg-warmdark-hover rounded-lg p-2 transition-colors focus:outline-none">✕</button>
        </div>
        
        <div class="flex-1 bg-gray-200 dark:bg-warmdark-bg relative p-4 sm:p-8 overflow-y-auto flex flex-col items-center w-full h-full custom-scrollbar">
            <div id="imageLoader" class="absolute inset-0 flex items-center justify-center pointer-events-none z-0">
                <div class="w-8 h-8 border-4 border-green-200 border-t-green-600 dark:border-t-green-500 rounded-full animate-spin"></div>
            </div>
            
            <img id="fileViewerImage" src="" alt="Proposal Certificate"
                class="w-full max-w-2xl h-auto relative z-10 shadow-lg border border-gray-300 dark:border-warmdark-border rounded hidden"
                onload="document.getElementById('imageLoader').classList.add('hidden'); this.classList.remove('hidden');">
                
            <iframe id="fileViewerPDF" src="" 
                class="w-full h-full relative z-10 shadow-lg border border-gray-300 dark:border-warmdark-border rounded hidden"
                onload="document.getElementById('imageLoader').classList.add('hidden'); this.classList.remove('hidden');"></iframe>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;
    
    // Pass the PHP variables to Javascript
    const systemCertUrl = <?php echo json_encode($global_cert_url); ?>;
    const systemCertType = <?php echo json_encode($cert_type); ?>;

    function fetchClearances(page = 1) {
        currentPage = page;
        const search = document.getElementById('clearanceSearch').value;
        const sy = document.getElementById('syFilter').value;
        const dept = document.getElementById('deptFilter').value;
        const status = document.getElementById('statusFilter').value;

        fetch(`../../backend/ajax/fetch_completed_proposals.php?p=${page}&search=${encodeURIComponent(search)}&sy=${encodeURIComponent(sy)}&dept=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                const tbody = document.getElementById('clearanceTableBody');
                tbody.innerHTML = '';

                if (data.completed.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-warmdark-bg text-gray-300 dark:text-gray-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-warmdark-border">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No proposals found matching your filters.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.completed.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-blue-50/30 dark:hover:bg-warmdark-hover transition-colors";

                    const services = [{
                            name: 'Grammarly',
                            status: row.grammarly_status
                        },
                        {
                            name: 'Ethics',
                            status: row.ethics_status
                        },
                        {
                            name: 'Grammarian',
                            status: row.hg_status
                        },
                        {
                            name: 'Librarian',
                            status: row.librarian_status
                        },
                        {
                            name: 'Statistician',
                            status: row.statistician_status
                        }
                    ];

                    let approvedCount = 0;
                    let serviceBadges = '';

                    services.forEach(s => {
                        if (s.status === 'Approved') {
                            approvedCount++;
                            serviceBadges += `<span class="inline-block bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 text-[9px] font-bold px-1.5 py-0.5 rounded border border-green-200 dark:border-green-500/30 m-0.5" title="Approved">${s.name}</span>`;
                        } else {
                            serviceBadges += `<span class="inline-block bg-gray-100 dark:bg-warmdark-bg text-gray-400 dark:text-gray-500 text-[9px] font-medium px-1.5 py-0.5 rounded border border-gray-200 dark:border-warmdark-border m-0.5 opacity-60" title="Pending/Rejected">${s.name}</span>`;
                        }
                    });

                    const isCleared = approvedCount === 5;

                    let statusHtml = '';
                    if (isCleared) {
                        statusHtml = `
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Cleared
                            </span>`;
                    } else {
                        statusHtml = `
                            <div class="flex flex-col items-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-900/50 mb-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                    Pending (${approvedCount}/5)
                                </span>
                                <div class="flex flex-wrap justify-center w-36">${serviceBadges}</div>
                            </div>`;
                    }

                    const timestamp = new Date().getTime();
                    const certificateUrl = `${systemCertUrl}?v=${timestamp}`;

                    let actionHtml = `
                        <div class="flex items-center justify-center gap-3">
                            <button onclick='openClearanceProfile(${JSON.stringify(row).replace(/'/g, "&#39;")})' 
                                class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 font-bold text-xs bg-gray-50 dark:bg-warmdark-bg hover:bg-blue-50 dark:hover:bg-warmdark-hover px-3 py-1.5 rounded-lg border border-gray-200 dark:border-warmdark-border hover:border-blue-200 dark:hover:border-blue-900/50 transition">
                                Profile
                            </button>
                    `;

                    if (isCleared) {
                        actionHtml += `
                            <button onclick="openFileViewer('${certificateUrl}', '${systemCertType}')" 
                                class="text-white font-bold text-xs bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-600 px-3 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 100 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                                Certificate
                            </button>
                        `;
                    }
                    actionHtml += `</div>`;

                    tr.innerHTML = `
                        <td class="px-6 py-4 text-center font-bold text-gray-400 dark:text-gray-500">${counter++}.</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900 dark:text-gray-200">${row.control_number}</div>
                        </td>
                        <td class="px-6 py-4 w-1/3">
                            <div class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2" title="${row.thesis_title}">${row.thesis_title}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            ${statusHtml}
                        </td>
                        <td class="px-6 py-4">
                            ${actionHtml}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination(data.totalPages, data.currentPage);
                const totalRows = data.totalRows || 0;
                const startRecord = totalRows > 0 ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                const statusTxt = document.getElementById('statusFilter').value === 'Cleared' ? 'Cleared' : 'Uncleared';
                document.getElementById('recordInfo').textContent = totalRows > 0 ? `Showing ${startRecord} - ${endRecord} of ${totalRows} ${statusTxt} Proposals` : '';
            })
            .catch(err => console.error(err));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchClearances(${currentPage - 1})" class="px-3 py-1.5 border border-gray-200 dark:border-warmdark-border rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchClearances(${i})" class="px-3 py-1.5 text-xs font-bold border border-gray-200 dark:border-warmdark-border rounded-lg shadow-sm transition ${i === currentPage ? 'bg-blue-600 dark:bg-blue-800 text-white border-blue-600 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchClearances(${currentPage + 1})" class="px-3 py-1.5 border border-gray-200 dark:border-warmdark-border font-bold text-gray-600 dark:text-gray-300 text-xs rounded-lg hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    function openFileViewer(url, type) {
        const img = document.getElementById('fileViewerImage');
        const pdf = document.getElementById('fileViewerPDF');
        const loader = document.getElementById('imageLoader');

        img.classList.add('hidden');
        pdf.classList.add('hidden');
        loader.classList.remove('hidden');

        if (type === 'pdf') {
            pdf.src = url;
        } else {
            img.src = url;
        }

        const modal = document.getElementById('fileViewerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeFileViewer() {
        const modal = document.getElementById('fileViewerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        setTimeout(() => {
            document.getElementById('fileViewerImage').src = '';
            document.getElementById('fileViewerPDF').src = '';
        }, 300);
    }

    document.getElementById('statusFilter').addEventListener('change', () => fetchClearances(1));
    document.getElementById('deptFilter').addEventListener('change', () => fetchClearances(1));
    document.getElementById('syFilter').addEventListener('change', () => fetchClearances(1));
    document.getElementById('clearanceSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchClearances(1), 400);
    });

    function openClearanceProfile(data) {
        if (typeof window.openProfileStudent === 'function') {
            window.openProfileStudent(data);
        }
        const statusSpan = document.getElementById("p_status");
        if (statusSpan && statusSpan.parentElement) {
            statusSpan.parentElement.style.display = 'none'; 
        }
    }

    document.addEventListener('DOMContentLoaded', () => fetchClearances(1));
</script>
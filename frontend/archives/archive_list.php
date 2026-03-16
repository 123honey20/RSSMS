<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}

// Fetch Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024;
$current_calendar_year = (int)date("Y"); 

// Generate up to 2 years into the future
$max_year = $current_calendar_year + 2; 

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $generated_years[] = $y . "-" . ($y + 1);
}

$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($archive_title); ?> Archives</h2>
            <p class="text-xs text-gray-500">View and manage all student document submissions.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <input type="text" id="archiveSearch" placeholder="Search by School ID..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

        <select id="syFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All School Years</option>
            <?php foreach ($generated_years as $year): ?>
                <option value="<?php echo $year; ?>" <?= ($year === $active_sy) ? 'selected' : '' ?>>
                    SY <?php echo $year; ?> <?= ($year === $active_sy) ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="deptFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All Departments</option>
            <?php while ($d = $departments->fetch_assoc()): ?>
                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <select id="statusFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-700 font-medium">
            <option value="All">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">School ID</th>
                    <th class="px-6 py-4 font-semibold">Thesis Title</th>
                    <th class="px-6 py-4 font-semibold text-center">Round</th>
                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Document</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="archiveTableBody" class="divide-y divide-gray-100"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 text-center pb-4 pr-6"></div>
    </div>
</div>

<div id="archiveModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Submission Details</h3>
            <button onclick="closeArchiveModal()" class="text-white hover:text-gray-200 text-sm">✕</button>
        </div>
        <div class="p-6 overflow-y-auto">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm text-gray-800">
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Student School ID</p>
                    <p class="font-bold text-gray-900" id="m_school_id"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">School Year</p>
                    <p class="font-medium text-gray-800" id="m_sy"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Department & Course</p>
                    <p class="font-medium text-gray-800" id="m_dept_course"></p>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 my-1"></div>

                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Thesis Title</p>
                    <p class="font-bold text-gray-900 leading-snug" id="m_thesis"></p>
                </div>

                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Submission Status</p>
                    <span id="m_status" class="inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide"></span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Submission Round</p>
                    <p class="font-medium text-gray-800">Round <span id="m_round"></span></p>
                </div>

                <div class="md:col-span-2 bg-blue-50 border border-blue-100 p-3 rounded-lg flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-200 text-blue-700 flex items-center justify-center font-bold">P</div>
                    <div>
                        <p class="text-blue-400 text-[10px] font-bold uppercase tracking-wider">Handled By Personnel</p>
                        <p class="font-bold text-blue-900" id="m_personnel"></p>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="closeArchiveModal()" class="bg-white border border-gray-300 px-6 py-2 rounded-lg hover:bg-gray-50 text-gray-700 text-sm font-bold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="fileViewerModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[9999] backdrop-blur-sm p-4 sm:p-8">
    <div class="bg-white w-full max-w-6xl h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col transform transition-all">
        <div class="bg-gray-900 text-white px-5 py-3 flex items-center justify-between shrink-0 shadow-md z-10">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="bg-blue-600/30 p-1.5 rounded-lg">
                    <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold tracking-wide truncate">Document Viewer</h3>
                <span id="viewer-filename" class="text-xs text-gray-400 font-medium truncate hidden sm:block"></span>
            </div>
            <button onclick="closeFileViewer()" class="text-gray-400 hover:text-red-400 hover:bg-gray-800 rounded-lg p-2 transition-colors focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 bg-gray-100 relative">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            </div>
            <iframe id="fileViewerIframe" src="" class="w-full h-full border-0 relative z-10 bg-white"></iframe>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;
    const currentTable = "<?php echo $archive_table; ?>"; // Dynamically passed from dashboard!

    function fetchArchives(page = 1) {
        currentPage = page;
        const search = document.getElementById('archiveSearch').value;
        const sy = document.getElementById('syFilter').value;
        const dept = document.getElementById('deptFilter').value;
        const status = document.getElementById('statusFilter').value;

        fetch(`../../backend/ajax/fetch_archives.php?table=${currentTable}&p=${page}&search=${encodeURIComponent(search)}&sy=${encodeURIComponent(sy)}&dept=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('archiveTableBody');
                tbody.innerHTML = '';

                if (data.records.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No submissions found.</td></tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    document.getElementById('paginationContainer').innerHTML = '';
                    return;
                }

                data.records.forEach(rec => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 transition";

                    let statusBadge = "bg-gray-100 text-gray-700";
                    if (rec.status === 'Pending') statusBadge = "bg-yellow-100 text-yellow-700";
                    if (rec.status === 'Approved') statusBadge = "bg-green-100 text-green-700";
                    if (rec.status === 'Rejected') statusBadge = "bg-red-100 text-red-700";

                    // Accurately map the table name to your specific folder structure
                    let folder = currentTable;
                    if (currentTable === 'grammarly_ai') {
                        folder = 'grammarly_ai/submissions';
                    }

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center font-semibold text-gray-800">${rec.school_id}</td>
                        <td class="px-6 py-4 font-medium text-gray-600 truncate max-w-[200px]" title="${rec.thesis_title}">${rec.thesis_title}</td>
                        <td class="px-6 py-4 text-center font-semibold">R${rec.round}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-md text-[10px] font-bold uppercase ${statusBadge}">${rec.status}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="openFileViewer('../../uploads/${folder}/${rec.file_path}', '${rec.file_path}')" class="text-blue-700 hover:underline text-xs inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View File
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-blue-700 hover:underline text-xs" onclick='openArchiveModal(${JSON.stringify(rec).replace(/'/g, "&#39;")})'>
                                Details
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);
                const startRec = (data.totalRows > 0) ? ((currentPage - 1) * 10 + 1) : 0;
                const endRec = Math.min(currentPage * 10, data.totalRows);
                document.getElementById('recordInfo').textContent = data.totalRows > 0 ? `Showing ${startRec} - ${endRec} of ${data.totalRows} records` : '';
            })
            .catch(error => console.error("Fetch Error:", error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchArchives(${currentPage - 1})" class="px-3 py-1 border rounded-md text-xs hover:bg-gray-50 shadow-sm">Prev</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchArchives(${i})" class="px-3 py-1 border rounded-md text-xs shadow-sm ${i === currentPage ? 'bg-blue-900 text-white' : 'hover:bg-gray-50'}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchArchives(${currentPage + 1})" class="px-3 py-1 border rounded-md text-xs hover:bg-gray-50 shadow-sm">Next</button>`;
        }
    }

    function openArchiveModal(data) {
        document.getElementById('m_school_id').textContent = data.school_id || 'N/A';
        document.getElementById('m_sy').textContent = data.school_year || 'N/A';
        document.getElementById('m_dept_course').textContent = (data.department_name || 'N/A') + " — " + (data.course_name || 'N/A');
        document.getElementById('m_thesis').textContent = data.thesis_title || 'N/A';
        document.getElementById('m_round').textContent = data.round || 'N/A';

        // Handle Personnel Name (Fallback to 'Pending Review' if null)
        document.getElementById('m_personnel').textContent = data.personnel_name ? data.personnel_name : 'Pending Review / System Auto';

        const statusEl = document.getElementById('m_status');
        statusEl.textContent = data.status;
        statusEl.className = "inline-block px-3 py-1 mt-1 text-[11px] rounded-full font-bold uppercase tracking-wide ";
        if (data.status === 'Pending') statusEl.className += "bg-yellow-100 text-yellow-700";
        if (data.status === 'Approved') statusEl.className += "bg-green-100 text-green-700";
        if (data.status === 'Rejected') statusEl.className += "bg-red-100 text-red-700";

        const modal = document.getElementById('archiveModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeArchiveModal() {
        const modal = document.getElementById('archiveModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Event Listeners for Filters
    ['syFilter', 'deptFilter', 'statusFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => fetchArchives(1));
    });

    document.getElementById('archiveSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchArchives(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchArchives(1));

    function openFileViewer(url, filename) {
        document.getElementById('viewer-filename').textContent = "— " + filename;
        
        // 1. Get the file extension
        const ext = filename.split('.').pop().toLowerCase();
        
        // 2. Define formats that browsers cannot natively read
        const officeFormats = ['doc', 'docx', 'ppt', 'pptx', 'odt', 'rtf'];
        let finalUrl = url;

        // 3. If it is an Office format, we try to use the Google Docs Viewer
        if (officeFormats.includes(ext)) {
            
            // Check if you are testing on localhost
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                alert("Note: Browsers cannot view Word/PPT files directly on 'localhost'. The file will safely download instead. Once your system is live on the internet, it will view directly in this window!");
                // Keep the original URL so the browser downloads it
            } else {
                // If your system is live on the internet, convert it to a Google Viewer link!
                // We must give Google the absolute (full) URL to your file
                const absoluteUrl = new URL(url, window.location.href).href;
                finalUrl = `https://docs.google.com/gview?url=${encodeURIComponent(absoluteUrl)}&embedded=true`;
            }
        }

        // 4. Load the URL into the iframe
        document.getElementById('fileViewerIframe').src = finalUrl;
        
        const modal = document.getElementById('fileViewerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeFileViewer() {
        const modal = document.getElementById('fileViewerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Clear the iframe src a moment later so it doesn't flash white while animating out
        setTimeout(() => {
            document.getElementById('fileViewerIframe').src = '';
        }, 300);
    }
</script>
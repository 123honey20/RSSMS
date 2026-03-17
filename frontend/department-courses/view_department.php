<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}
?>

<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border transition-colors duration-200">
    
    <div class="flex items-center justify-between mb-6 border-b border-transparent dark:border-warmdark-border pb-2 transition-colors">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">List of Departments</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_department"
            class="bg-blue-900 dark:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition shadow-sm">
            + Add Department
        </a>
    </div>

    <div class="mb-4">
        <input
            type="text"
            id="departmentSearch"
            placeholder="Search Department Name"
            class="w-full md:w-1/2 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">Department Name</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="departmentTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

    function fetchDepartments(page = 1) {
        currentPage = page;
        const search = document.getElementById('departmentSearch').value;

        fetch(`../../backend/ajax/fetch_departments.php?p=${page}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('departmentTableBody');
                tbody.innerHTML = '';

                if (data.departments.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No Departments Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.departments.forEach(dept => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                    row.innerHTML = `
                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${counter++}.</td>
                        <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200">${dept.name}</td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <a href="../dashboards/admin_dashboard.php?page=edit_department&id=${dept.id}"
                                class="text-blue-700 dark:text-blue-400 px-4 py-1.5 hover:underline text-xs">
                                Update
                            </a>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);

                const startRecord = (data.totalRows > 0) ? ((page - 1) * 10 + 1) : 0;
                const endRecord = Math.min(page * 10, data.totalRows);
                document.getElementById('recordInfo').textContent = `Showing ${startRecord} - ${endRecord} of ${data.totalRows} Departments`;
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchDepartments(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchDepartments(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchDepartments(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    document.getElementById('departmentSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchDepartments(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchDepartments(1));
</script>
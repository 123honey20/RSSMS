<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}
?>

<div class="bg-white p-6 rounded-xl shadow">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">List of Departments</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_department"
            class="bg-blue-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            + Add Department
        </a>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <input
            type="text"
            id="departmentSearch"
            placeholder="Search Department Name"
            class="w-full md:w-1/2 border rounded px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border-b text-xs text-center">#</th>
                    <th class="p-3 border-b text-xs">Department Name</th>
                    <th class="p-3 border-b text-xs text-center">Action</th>
                </tr>
            </thead>
            <tbody id="departmentTableBody"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-600 text-center"></div>

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
                            <td colspan="3" class="text-center p-4 text-gray-500">
                                No Departments Found.
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.departments.forEach(dept => {
                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50 transition";

                    row.innerHTML = `
                        <td class="p-3 text-xs border-b text-center">${counter++}.</td>
                        <td class="p-3 border-b">${dept.name}</td>
                        <td class="p-3 border-b text-center">
                            <a href="../dashboards/admin_dashboard.php?page=edit_department&id=${dept.id}"
                                class="text-blue-700 hover:underline text-sm">
                                Update
                            </a>
                        </td>
                    `;

                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);

                const startRecord = (data.totalRows > 0) ? ((page - 1) * 10 + 1) : 0;
                const endRecord = Math.min(page * 10, data.totalRows);
                document.getElementById('recordInfo').textContent =
                    `Showing ${startRecord} - ${endRecord} of ${data.totalRows} Departments`;
            })
            .catch(error => {
                console.error(error);
                document.getElementById('departmentTableBody').innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center p-4 text-red-500">
                            Failed to load departments.
                        </td>
                    </tr>`;
                    document.getElementById('recordInfo').textContent = '';
            });
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `
                <button onclick="fetchDepartments(${currentPage - 1})"
                    class="px-2 py-1 border rounded text-xs hover:bg-gray-100">
                    Prev
                </button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `
                <button onclick="fetchDepartments(${i})"
                    class="px-2 py-1 text-xs border rounded
                    ${i === currentPage ? 'bg-blue-900 text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `
                <button onclick="fetchDepartments(${currentPage + 1})"
                    class="px-2 py-1 border text-xs rounded hover:bg-gray-100">
                    Next
                </button>`;
        }
    }

    document.getElementById('departmentSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchDepartments(1);
        }, 400);
    });

    document.addEventListener('DOMContentLoaded', () => {
        fetchDepartments(1);
    });
</script>
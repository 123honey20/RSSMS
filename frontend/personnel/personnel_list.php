<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    exit("Access denied");
}
?>


<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Personnel Accounts</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_personnel"
            class="bg-blue-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            + Add Personnel
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">

        <!-- Search -->
        <input
            type="text"
            id="personnelSearch"
            placeholder="Search by School ID"
            class="w-full md:w-1/2 border rounded px-3 py-2 text-sm">

        <!-- Filter Buttons -->
        <div class="flex gap-2">
            <button onclick="setStatusFilter('Pending')"
                class="text-blue-700 px-3 py-2 rounded hover:underline text-xs">
                Pending
            </button>

            <h2 class="px-1 py-1">-</h2>

            <button onclick="setStatusFilter('Approved')"
                class="text-blue-700 px-3 py-2 rounded hover:underline text-xs">
                Approved
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border-b text-xs text-center">#</th>
                    <th class="p-3 border-b text-xs">School ID</th>
                    <th class="p-3 border-b text-xs text-center">Status</th>
                    <th class="p-3 border-b text-xs text-center">Profile</th>
                    <th class="p-3 border-b text-xs text-center">Action</th>
                </tr>
            </thead>
            <tbody id="personnelTableBody"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-600 text-center"></div>
    </div>
</div>

<!-- Personnel Profile -->

<div id="profileModalPersonnel" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden">

        <div class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Personnel Profile</h3>
            <button onclick="closeProfilePersonnel()" class="text-white hover:text-gray-200 text-sm">
                ✕
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">

                <div>
                    <p class="text-gray-500 text-xs uppercase">School ID</p>
                    <p class="font-medium" id="p_school_id"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase">Email</p>
                    <p class="font-medium" id="p_email"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase">Full Name</p>
                    <p class="font-medium" id="p_full_name"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase">Department</p>
                    <p class="font-medium" id="p_department_id"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase">Service Role</p>
                    <p class="font-medium" id="p_service_role"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase">Status</p>
                    <span id="p_status"
                        class="inline-block px-3 py-1 mt-2 text-xs rounded-full font-semibold bg-green-100 text-green-700">
                    </span>
                </div>

            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeProfilePersonnel()"
                    class="bg-gray-200 px-5 py-2 rounded-lg hover:bg-gray-300 text-sm font-medium">
                    Close
                </button>
            </div>
        </div>

    </div>
</div>


<script>
    let currentPage = 1;
    let currentStatusFilter = 'All';
    let searchTimeout;

    function fetchPersonnel(page = 1) {
        currentPage = page;

        const search = document.getElementById('personnelSearch').value;

        fetch(`../../backend/ajax/fetch_personnel.php?p=${page}&search=${encodeURIComponent(search)}&status=${currentStatusFilter}`)
            .then(response => response.json())
            .then(data => {

                const tbody = document.getElementById('personnelTableBody');
                tbody.innerHTML = '';

                if (data.personnel.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center p-4 text-gray-500">
                                No Personnel Accounts Found.
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                    return;
                }

                let counter = (data.currentPage - 1) * 10 + 1;

                data.personnel.forEach(person => {

                    const row = document.createElement('tr');
                    row.className = "hover:bg-gray-50 transition";

                    row.innerHTML = `
                        <td class="p-3 text-xs border-b text-center">${counter++}.</td>
                        <td class="p-3 border-b"></td>
                        <td class="p-3 border-b text-center"></td>
                        <td class="p-3 border-b text-center">
                            <button class="text-blue-700 hover:underline text-sm"
                                onclick='openProfilePersonnel(${JSON.stringify(person)})'>
                                View
                            </button>
                        </td>
                        <td class="p-3 border-b text-center">
                            <a href="../dashboards/admin_dashboard.php?page=edit_personnel&id=${person.id}"
                                class="text-blue-700 hover:underline text-sm">
                                Update
                            </a>
                        </td>
                    `;

                    row.children[1].textContent = person.school_id;

                    if (person.status === 'Pending') {
                        row.children[2].innerHTML = `
                            <form action="../../backend/actions/approve_user.php" method="POST">
                                <input type="hidden" name="user_id" value="${person.id}">
                                <button type="submit"
                                    class="bg-blue-900 text-white px-3 py-1 rounded hover:bg-blue-800 text-sm">
                                    Approve
                                </button>
                            </form>
                        `;
                    } else {
                        row.children[2].innerHTML = `
                            <span class="px-3 py-1 text-xs rounded-full text-green-700 font-semibold">
                                Approved
                            </span>
                        `;
                    }

                    tbody.appendChild(row);
                });

                renderPagination(data.totalPages, data.currentPage);

                const totalRows = data.totalRows || 0;
                const startRecord = (totalRows > 0) ? ((currentPage - 1) * 10 + 1) : 0;
                const endRecord = Math.min(currentPage * 10, totalRows);

                document.getElementById('recordInfo').textContent =
                    totalRows > 0 ?
                    `Showing ${startRecord} - ${endRecord} of ${totalRows} Personnel` :
                    '';

            })
            .catch(error => {
                console.error(error);
            });
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `
                <button onclick="fetchPersonnel(${currentPage - 1})"
                    class="px-2 py-1 border rounded text-xs hover:bg-gray-100">
                    Prev
                </button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `
                <button onclick="fetchPersonnel(${i})"
                    class="px-2 py-1 text-xs border rounded
                    ${i === currentPage ? 'bg-blue-900 text-white' : 'hover:bg-gray-100'}">
                    ${i}
                </button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `
                <button onclick="fetchPersonnel(${currentPage + 1})"
                    class="px-2 py-1 border text-xs rounded hover:bg-gray-100">
                    Next
                </button>`;
        }
    }

    function setStatusFilter(status) {
        currentStatusFilter = (currentStatusFilter === status) ? 'All' : status;
        fetchPersonnel(1);
    }

    document.getElementById('personnelSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchPersonnel(1);
        }, 400);
    });

    document.addEventListener('DOMContentLoaded', () => {
        fetchPersonnel(1);
    });
</script>
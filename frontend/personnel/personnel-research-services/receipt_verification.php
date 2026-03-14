<?php
if ($_SESSION['service_role'] !== 'Grammarly & AI Checking') {
    die("Access Denied");
}
?>

<div class="bg-white p-6 rounded-xl shadow min-h-[80vh]">

    <div class="flex items-center justify-between mb-6 pb-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Receipt Verification for Grammarly & AI Checking Service</h2>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input type="text" id="searchInput" placeholder="Search by Control Number..."
            class="w-full md:w-1/3 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-900 focus:outline-none shadow-sm">
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

</div>

<script>
    function fetchData() {
        const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';

        fetch(`../../backend/ajax/fetch_receipt_verification.php`)
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
                    return;
                }

                res.data.forEach(row => {
                    let actionButton = '';
                    if (row.status === 'Approved' || row.status === 'Rejected') {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm whitespace-nowrap">Receipt Review</button>`;
                    } else {
                        actionButton = `<button onclick="loadReceiptProcess(${row.id})" class="text-gray-600 bg-gray-100 border border-gray-200 px-4 py-1.5 rounded-lg hover:bg-gray-200 transition font-bold text-xs shadow-sm whitespace-nowrap">Process</button>`;
                    }

                    // Department column removed from here
                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-center text-xs text-gray-600 align-middle whitespace-nowrap">
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
            })
            .catch(error => console.error("Error fetching data:", error));
    }

    function updateStatus(id, status) {
        fetch("../../backend/ajax/update_receipt_verification.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id,
                    status
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert("Status Updated Successfully");
                    fetchData();
                } else {
                    alert("Update Failed");
                }
            });
    }

    // Status Badge Styling (Untouched)
    function getStatusBadge(status) {
        if (status === 'Approved') {
            return `<span class="text-green-700 font-bold px-3 py-1.5 text-xs">Approved</span>`;
        }
        if (status === 'Rejected') {
            return `<span class="text-red-700 font-bold px-3 py-1.5 text-xs">Rejected</span>`;
        }
        if (status === 'Receipt Uploaded') {
            return `<span class="text-yellow-700 font-bold px-3 py-1.5 text-xs">Receipt Uploaded</span>`;
        }
        return `<span class="bg-gray-50 text-gray-700 font-bold px-3 py-1.5 rounded-md text-xs border border-gray-200">${status}</span>`;
    }

    // Process Receipt of the Student
    function loadReceiptProcess(id) {
        const url = `../personnel/personnel-access-file/process_receipt_verification.php?id=${id}`;
        fetch(url)
            .then(res => res.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
            })
            .catch(err => console.error(err));
    }

    // Basic debounced search 
    let searchTimeout;
    if (document.getElementById('searchInput')) {
        document.getElementById('searchInput').addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchData();
            }, 400);
        });
    }

    document.addEventListener("DOMContentLoaded", fetchData);
</script>
<?php
if ($_SESSION['service_role'] !== 'Grammarly & AI Checking') {
    die("Access Denied");
}
?>

<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Receipt Verification for Grammarly & AI Checking Service</h2>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input
            type="text"
            placeholder="Search by Control Number"
            class="w-full md:w-1/2 border rounded px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 text-gray-700 text-xs">
                <tr>
                    <th class="px-4 py-3  text-center">Round</th>
                    <th class="px-4 py-3">Control No.</th>
                    <th class="px-4 py-3 text-center">Department</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Receipt</th>
                </tr>
            </thead>

            <tbody id="tableBody" class="divide-y"></tbody>
        </table>
    </div>

</div>

<script>
    function fetchData() {

        fetch("../../backend/ajax/fetch_receipt_verification.php")
            .then(res => res.json())
            .then(res => {

                const tbody = document.getElementById("tableBody");
                tbody.innerHTML = "";

                if (!res.data || res.data.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center p-4 text-gray-500">
                            No uploaded receipts found
                        </td>
                    </tr>
                `;
                    return;
                }
                res.data.forEach(row => {

                    tbody.innerHTML += `
                        <tr class="border-b hover:bg-gray-50">

                            <td class="px-4 py-3 text-xs text-center">
                                Round ${row.round}
                            </td>

                            <td class="px-4 py-3">
                                ${row.control_number}
                            </td>

                            <td class="px-4 py-3 text-center">
                                ${row.department_name}
                            </td>

                            <td class="px-4 py-3 text-center">
                                ${getStatusBadge(row.status)}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <button onclick="loadReceiptProcess(${row.id})"
                                class="text-blue-700 hover:underline text-sm">
                                    Process
                                </button>
                            </td>

                        </tr>
                        `;

                });

            });

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

    // Status
    function getStatusBadge(status) {

        if (status === 'Approved') {
            return `<span class="px-3 py-1 text-xs text-center rounded-full text-green-700 font-semibold">
                    Approved
                </span>`;
        }

        if (status === 'Rejected') {
            return `<span class="px-3 py-1 text-xs text-center rounded-full text-red-700 font-semibold">
                    Rejected
                </span>`;
        }

        if (status === 'Receipt Uploaded') {
            return `<span class="px-3 py-1 text-xs text-center rounded-full text-yellow-700 font-semibold">
                    Receipt Uploaded
                </span>`;
        }

        return `<span class="px-3 py-1 text-xs rounded-full text-gray-700 font-semibold">
                ${status}
            </span>`;
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

    document.addEventListener("DOMContentLoaded", fetchData);
</script>
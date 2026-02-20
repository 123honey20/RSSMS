<div class="bg-white p-6 rounded-xl shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">List of Courses</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_course"
            class="bg-blue-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            + Add Course
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input
            type="text"
            id="courseSearch"
            placeholder="Search by Course Name"
            class="w-full md:w-1/2 border rounded px-3 py-2 text-sm">
    </div>


    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="p-3 border-b text-xs text-center">#</th>
                    <th class="p-3 border-b text-xs">Course</th>
                    <th class="p-3 border-b text-xs text-center">Department</th>
                    <th class="p-3 border-b text-xs text-center">Action</th>
                </tr>
            </thead>

            <tbody id="courseTableBody">
            </tbody>

        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-600 text-center"></div>


        <div class="flex justify-end mt-3 text-xs text-gray-600 text-center">
        </div>

    </div>

</div>

<script>
    let currentPage = 1;

    function fetchCourses(page = 1) {
        const search = document.getElementById('courseSearch').value;

        fetch(`../../backend/ajax/fetch_courses.php?p=${page}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(data => {

                const tbody = document.getElementById('courseTableBody');
                tbody.innerHTML = '';

                if (data.courses.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center p-4 text-gray-500">
                            No Courses Found.
                        </td>
                    </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                } else {

                    let counter = (data.currentPage - 1) * 10 + 1;

                    data.courses.forEach(course => {
                        const row = document.createElement('tr');
                        row.className = "hover:bg-gray-50 transition";

                        row.innerHTML = `
                            <td class="p-3 text-xs border-b text-center">${counter++}.</td>
                            <td class="p-3 border-b"></td>
                            <td class="p-3 border-b text-center"></td>
                            <td class="p-3 border-b text-center">
                                <a href="../dashboards/admin_dashboard.php?page=edit_course&id=${course.id}"
                                   class="text-blue-700 hover:underline text-sm">
                                   Update
                                </a>
                            </td>
                        `;

                        row.children[1].textContent = course.course_name;
                        row.children[2].textContent = course.department_name;

                        tbody.appendChild(row);
                    });

                }

                renderPagination(data.totalPages, data.currentPage);

                const startRecord = (data.totalRows > 0) ? ((page - 1) * 10 + 1) : 0;
                const endRecord = Math.min(page * 10, data.totalRows);
                document.getElementById('recordInfo').textContent =
                    `Showing ${startRecord} - ${endRecord} of ${data.totalRows} Courses`;
            })
            .catch(error => {
                console.error(error);
                document.getElementById('courseTableBody').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center p-4 text-red-500">
                            Failed to load courses.
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
            <button onclick="fetchCourses(${currentPage - 1})"
                class="px-2 py-1 border rounded text-xs hover:bg-gray-100">
                Prev
            </button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `
            <button onclick="fetchCourses(${i})"
                class="px-2 py-1 text-xs border rounded
                ${i === currentPage ? 'bg-blue-900 text-white' : 'hover:bg-gray-100'}">
                ${i}
            </button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `
            <button onclick="fetchCourses(${currentPage + 1})"
                class="px-2 py-1 border text-xs rounded hover:bg-gray-100">
                Next
            </button>`;
        }
    }

    let searchTimeout;

    document.getElementById('courseSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchCourses(1);
        }, 400);
    });


    document.addEventListener('DOMContentLoaded', () => {
        fetchCourses(1);
    });
</script>
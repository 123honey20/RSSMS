<div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-transparent dark:border-warmdark-border transition-colors duration-200">

    <div class="flex items-center justify-between mb-6 border-b border-transparent dark:border-warmdark-border pb-2 transition-colors">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">List of Courses</h2>
        <a href="../dashboards/admin_dashboard.php?page=add_course"
            class="bg-blue-900 dark:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-800 dark:hover:bg-blue-700 transition shadow-sm">
            + Add Course
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input
            type="text"
            id="courseSearch"
            placeholder="Search by Course Name"
            class="w-full md:w-1/2 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition-colors shadow-sm">
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-warmdark-border transition-colors">
        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-warmdark-border transition-colors">
                <tr>
                    <th class="px-6 py-4 font-semibold text-center">#</th>
                    <th class="px-6 py-4 font-semibold">Course Name</th>
                    <th class="px-6 py-4 font-semibold text-center">Department</th>
                    <th class="px-6 py-4 font-semibold text-center">Action</th>
                </tr>
            </thead>
            <tbody id="courseTableBody" class="divide-y divide-gray-100 dark:divide-warmdark-border"></tbody>
        </table>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2 text-sm pb-4"></div>
        <div id="recordInfo" class="flex justify-end mt-2 text-xs text-gray-500 dark:text-gray-400 text-center pb-4 pr-6"></div>
    </div>
</div>

<script>
    let currentPage = 1;
    let searchTimeout;

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
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477-4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="font-medium text-gray-500 dark:text-gray-400">No Courses Found.</p>
                            </td>
                        </tr>`;
                    document.getElementById('recordInfo').textContent = '';
                } else {
                    let counter = (data.currentPage - 1) * 10 + 1;

                    data.courses.forEach(course => {
                        const row = document.createElement('tr');
                        row.className = "hover:bg-gray-50/50 dark:hover:bg-warmdark-hover transition-colors";

                        row.innerHTML = `
                            <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">${counter++}.</td>
                            <td class="px-6 py-4 font-semibold text-gray-800 dark:text-gray-200"></td>
                            <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-300"></td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <a href="../dashboards/admin_dashboard.php?page=edit_course&id=${course.id}"
                                   class="text-blue-700 dark:text-blue-400 px-4 py-1.5 hover:underline text-xs">
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
                document.getElementById('recordInfo').textContent = `Showing ${startRecord} - ${endRecord} of ${data.totalRows} Courses`;
            })
            .catch(error => console.error(error));
    }

    function renderPagination(totalPages, currentPage) {
        const container = document.getElementById('paginationContainer');
        container.innerHTML = '';
        if (totalPages <= 1) return;

        if (currentPage > 1) {
            container.innerHTML += `<button onclick="fetchCourses(${currentPage - 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            container.innerHTML += `<button onclick="fetchCourses(${i})" class="px-3 py-1 text-xs border border-gray-200 dark:border-warmdark-border rounded-md shadow-sm transition ${i === currentPage ? 'bg-blue-900 dark:bg-blue-800 text-white border-blue-900 dark:border-blue-800' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-warmdark-hover'}">${i}</button>`;
        }

        if (currentPage < totalPages) {
            container.innerHTML += `<button onclick="fetchCourses(${currentPage + 1})" class="px-3 py-1 border border-gray-200 dark:border-warmdark-border text-gray-600 dark:text-gray-300 text-xs rounded-md hover:bg-gray-50 dark:hover:bg-warmdark-hover transition shadow-sm">Next</button>`;
        }
    }

    document.getElementById('courseSearch').addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchCourses(1), 400);
    });

    document.addEventListener('DOMContentLoaded', () => fetchCourses(1));
</script>
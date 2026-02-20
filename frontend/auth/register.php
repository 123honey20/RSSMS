<?php
require_once "../../backend/config/database.php";

$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-200 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-lg rounded-xl overflow-hidden w-full max-w-4xl p-10 mb-20 mt-10">

        <div class="flex items-center gap-3 mb-8">
            <img src="../images/smcc logo.png" class="w-11 h-11">
            <span class="font-semibold text-gray-700">Saint Michael College of Caraga</span>
        </div>

        <h1 class="text-lg font-bold mb-2">REGISTRATION</h1>
        <p class="text-xs text-gray-500 mb-8">Please fill out the form below to complete your registration for RSSMS</p>

        <div class="mb-6">
            <p id="passwordError" class="text-red-600 text-xs mt-1 hidden">
                Password and Confirm Password do not match.
            </p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">I am a</label>
            <select id="roleSelect" class="bg-gray-100 rounded-lg px-3 py-2 w-60 focus:outline-none" onchange="switchRole()">
                <option value="student" selected>Student</option>
                <option value="personnel">Personnel</option>
            </select>
        </div>

        <!-- FORM -->
        <form id="registerForm" method="POST" action="../../backend/actions/register_student_action.php">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="text-sm">School ID</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5.121 17.804A4 4 0 019 15h6a4 4 0 013.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <input type="text" name="school_id" required
                            class="w-full bg-transparent focus:outline-none" placeholder="Enter School ID">
                    </div>

                </div>

                <div>
                    <label class="text-sm">Email</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 8l9 6 9-6M4 6h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z" />
                        </svg>
                        <input type="email" name="email" required
                            class="w-full bg-transparent focus:outline-none" placeholder="Enter Email">
                    </div>

                </div>

                <div>
                    <label class="text-sm">Password</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 11c1.657 0 3 1.343 3 3v3H9v-3c0-1.657 1.343-3 3-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 11V7a5 5 0 00-10 0v4" />
                        </svg>
                        <input type="password" id="password" name="password" required
                            class="w-full bg-transparent focus:outline-none" placeholder="Enter Password">
                    </div>

                </div>

                <div>
                    <label class="text-sm">Confirm Password</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 11c1.657 0 3 1.343 3 3v3H9v-3c0-1.657 1.343-3 3-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 11V7a5 5 0 00-10 0v4" />
                        </svg>
                        <input type="password" id="confirm_password" placeholder="Confirm Password" name="confirm_password" required
                            class="w-full bg-transparent focus:outline-none">
                    </div>
                </div>


                <!-- STUDENT FIELDS -->
                <div id="studentFields" class="contents">

                    <div>
                        <label class="text-sm">Thesis Title</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6h8M12 10h8M12 14h8M4 6h4v12H4z" />
                            </svg>
                            <input type="text" name="thesis_title" class="w-full bg-transparent focus:outline-none" placeholder="Enter Thesis Title">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Control Number</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 20h10M9 4h6v16H9z" />
                            </svg>
                            <input type="text" name="control_number" class="w-full bg-transparent focus:outline-none" placeholder="Enter Control Number">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Research Leader</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A4 4 0 019 15h6a4 4 0 013.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <input type="text" name="research_leader" class="w-full bg-transparent focus:outline-none" placeholder="Enter Research Leader">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Department</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M9 21V10m6 11V10M5 6h14l-1-3H6L5 6z" />
                            </svg>
                            <select name="student_department_id" id="departmentSelect" required>
                                <option value="">Select Department</option>
                                <?php while ($d = $departments->fetch_assoc()): ?>
                                    <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Course</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0121 13.5c0 2.485-4.03 4.5-9 4.5S3 15.985 3 13.5a12.083 12.083 0 012.84-2.922L12 14z" />
                            </svg>
                            <select name="course_id" id="courseSelect"
                                required class="w-full border p-2 rounded">

                                <option value="">Select Course</option>
                            </select>



                        </div>
                    </div>

                </div>

                <!-- PERSONNEL FIELDS -->
                <div id="personnelFields" class="contents hidden">

                    <div>
                        <label class="text-sm">Full Name</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A4 4 0 019 15h6a4 4 0 013.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <input type="text" name="full_name" class="w-full bg-transparent focus:outline-none" placeholder="Enter Full Name">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Department</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M9 21V10m6 11V10M5 6h14l-1-3H6L5 6z" />
                            </svg>
                            <select name="personnel_department_id"
                                required
                                class="w-full bg-transparent focus:outline-none border rounded p-2">
                                <option value="">Select Department</option>

                                <?php
                                $departments->data_seek(0);
                                while ($row = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?= $row['id']; ?>">
                                        <?= htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                        </div>
                    </div>

                    <div>
                        <label class="text-sm">Role</label>
                        <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M7 4h10v16H7z" />
                            </svg>
                            <select name="role" class="w-full bg-transparent focus:outline-none">
                                <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                                <option value="Human Grammarian">Human Grammarian</option>
                                <option value="Statistician">Statistician</option>
                                <option value="Librarian">Librarian</option>
                                <option value="Ethics">Ethics</option>
                            </select>
                        </div>
                    </div>

                </div>


            </div>

            <div class="flex justify-end gap-4 mt-10">
                <a href="login.php" class="text-sm bg-gray-300 px-7 py-3 rounded-lg">Back</a>
                <button type="submit" class="bg-blue-900 text-sm text-white px-10 py-3 rounded-lg">Register</button>
            </div>

        </form>

    </div>

    <script>
        function switchRole() {
            const role = document.getElementById("roleSelect").value;
            const studentFields = document.getElementById("studentFields");
            const personnelFields = document.getElementById("personnelFields");
            const form = document.getElementById("registerForm");

            const studentInputs = studentFields.querySelectorAll("input, select");
            const personnelInputs = personnelFields.querySelectorAll("input, select");

            if (role === "student") {
                studentFields.classList.remove("hidden");
                personnelFields.classList.add("hidden");
                form.action = "../../backend/actions/register_student_action.php";

                studentInputs.forEach(el => el.required = true);
                personnelInputs.forEach(el => el.required = false);
            } else {
                studentFields.classList.add("hidden");
                personnelFields.classList.remove("hidden");
                form.action = "../../backend/actions/register_personnel_action.php";

                studentInputs.forEach(el => el.required = false);
                personnelInputs.forEach(el => el.required = true);
            }
        }

        document.getElementById("registerForm").addEventListener("submit", function(e) {
            const password = document.getElementById("password").value.trim();
            const confirmPassword = document.getElementById("confirm_password").value.trim();
            const passwordError = document.getElementById("passwordError");
            passwordError.classList.add("hidden");

            // 1. Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                passwordError.classList.remove("hidden");
                return;
            }

            const role = document.getElementById("roleSelect").value;
            const container = role === "student" ?
                document.getElementById("studentFields") :
                document.getElementById("personnelFields");

            const requiredFields = container.querySelectorAll("input[required], select[required]");

            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    e.preventDefault();
                    alert("Please fill in all required fields before registering.");
                    field.focus();
                    return;
                }
            }
        });

        switchRole();

        // Use this instead
        document.getElementById("departmentSelect").addEventListener("change", function() {
            const departmentId = this.value;
            const courseSelect = document.getElementById("courseSelect");

            courseSelect.innerHTML = '<option value="">Loading...</option>';

            if (!departmentId) {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                return;
            }

            fetch("../../backend/ajax/get_courses.php?department_id=" + departmentId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Select Course</option>';
                    data.forEach(course => {
                        options += `<option value="${course.id}">${course.name}</option>`;
                    });
                    courseSelect.innerHTML = options;
                })
                .catch(error => {
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
        });
    </script>




</body>

</html>
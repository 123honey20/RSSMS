<?php
require_once "../../backend/config/database.php";

// Fetch Departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");

$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | RSSMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }
        
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        warmdark: {
                            bg: '#121212',
                            panel: '#1e1e1e',
                            border: '#333333'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .bg-pattern { background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%232563eb" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E'); }

        /* FIX BROWSER AUTOFILL STYLING FOR LIGHT MODE */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #f9fafb inset !important; /* bg-gray-50 */
            -webkit-text-fill-color: #1f2937 !important; /* text-gray-800 */
        }

        /* FIX BROWSER AUTOFILL STYLING FOR DARK MODE */
        .dark input:-webkit-autofill,
        .dark input:-webkit-autofill:hover, 
        .dark input:-webkit-autofill:focus, 
        .dark input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #121212 inset !important; /* match warmdark-bg */
            -webkit-text-fill-color: white !important; /* white text */
            caret-color: white;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-warmdark-bg min-h-screen font-sans text-gray-800 dark:text-gray-200 antialiased selection:bg-blue-200 selection:text-blue-900 bg-pattern flex items-center justify-center p-4 transition-colors duration-300">

    <div class="w-full max-w-5xl bg-white dark:bg-warmdark-panel rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row border dark:border-warmdark-border" x-data="registrationApp()">
        
        <div class="w-full md:w-5/12 bg-blue-900 p-10 flex flex-col justify-between text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full bg-blue-800 opacity-20 transform -skew-y-12 scale-150 origin-top-left"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-10">
                    <div class="bg-white p-2 rounded-xl shadow-md">
                        <img src="../images/smcc logo.png" class="w-10 h-10 object-contain">
                    </div>
                    <div>
                        <h2 class="font-bold text-lg tracking-tight leading-tight">SMCC</h2>
                        <p class="text-[10px] text-blue-200 font-medium tracking-widest uppercase">Research Support</p>
                    </div>
                </div>

                <h1 class="text-2xl font-bold mb-4 leading-tight">Research Support Services<br>Monitoring System</h1>
                <p class="text-blue-100 text-sm leading-relaxed mb-8 opacity-80">Register your account to manage your thesis documents, connect with research personnel, and track your academic progress.</p>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3 text-blue-200 text-sm font-medium">
                        <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Department-Specific Academic Support
                    </div>
                    <div class="flex items-center gap-3 text-blue-200 text-sm font-medium">
                        <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Centralized Thesis Monitoring
                    </div>
                    <div class="flex items-center gap-3 text-blue-200 text-sm font-medium">
                        <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Direct Chat with Research Personnel
                    </div>
                </div>
            </div>

            <div class="relative z-10 mt-12 text-xs text-blue-300">
                Already have an account? <br>
                <a href="login.php" class="text-white font-bold hover:underline mt-1 inline-block">Sign in here &rarr;</a>
            </div>
        </div>

        <div class="w-full md:w-7/12 p-10 bg-white dark:bg-warmdark-panel">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Create Account</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select your role and fill in your details.</p>
                </div>
                
                <div class="flex bg-gray-100 dark:bg-warmdark-bg p-1 rounded-lg border dark:border-warmdark-border border-gray-200">
                    <button @click="role = 'student'" :class="role === 'student' ? 'bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400' : 'text-gray-500 dark:text-gray-500'" class="px-4 py-1.5 rounded-md text-xs font-bold transition-all">
                        Student
                    </button>
                    <button @click="role = 'personnel'" :class="role === 'personnel' ? 'bg-white dark:bg-warmdark-panel shadow-sm text-blue-700 dark:text-blue-400' : 'text-gray-500 dark:text-gray-500'" class="px-4 py-1.5 rounded-md text-xs font-bold transition-all">
                        Personnel
                    </button>
                </div>
            </div>

            <div x-show="errorMsg" class="mb-6 p-3 bg-red-50 border border-red-100 rounded-lg flex items-start gap-3" x-cloak>
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p class="text-sm font-medium text-red-700" x-text="errorMsg"></p>
            </div>

            <form :action="role === 'student' ? '../../backend/actions/register_student_action.php' : '../../backend/actions/register_personnel_action.php'" method="POST" @submit="validateForm">
                
                <input type="hidden" name="school_year" value="<?php echo htmlspecialchars($active_sy); ?>">
                <input type="hidden" name="role" :value="role">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">School ID</label>
                        <input type="text" name="school_id" required class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Enter School ID">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email Address</label>
                        <input type="email" name="email" required class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Enter Email">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" x-model="password" required minlength="6" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" x-model="confirm_password" required minlength="6" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="••••••••">
                    </div>
                </div>

                <div class="w-full h-px bg-gray-100 dark:bg-warmdark-border my-6"></div>

                <div x-show="role === 'student'" x-transition x-cloak class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Control Number</label>
                            <input type="text" name="control_number" :required="role === 'student'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Control No.">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Research Leader</label>
                            <input type="text" name="research_leader" :required="role === 'student'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Full Name">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Thesis Title</label>
                        <input type="text" name="thesis_title" :required="role === 'student'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Thesis Title">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Department</label>
                            <select name="student_department_id" id="student_department_id" :required="role === 'student'" @change="fetchCourses" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors appearance-none">
                                <option value="">Select Department</option>
                                <?php
                                $departments->data_seek(0);
                                while ($d = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Course</label>
                            <select name="course_id" id="courseSelect" :required="role === 'student'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors appearance-none">
                                <option value="">Select Course</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="role === 'personnel'" x-transition x-cloak class="space-y-5">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Full Name</label>
                        <input type="text" name="full_name" :required="role === 'personnel'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors" placeholder="Enter Full Name">
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Service Role</label>
                            <select name="role" x-model="personnelServiceRole" :required="role === 'personnel'" class="w-full bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 dark:text-white transition-colors appearance-none">
                                <option value="">Select Role</option>
                                <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                                <option value="Human Grammarian">Human Grammarian</option>
                                <option value="Statistician">Statistician</option>
                                <option value="Librarian">Librarian</option>
                                <option value="Ethics">Ethics</option>
                            </select>
                        </div>

                        <div x-show="personnelServiceRole !== 'Grammarly & AI Checking' && personnelServiceRole !== ''">
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Departments</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto p-3 border border-gray-200 dark:border-warmdark-border rounded-lg bg-gray-50 dark:bg-warmdark-bg shadow-inner">
                                <?php
                                $departments->data_seek(0); 
                                while ($d = $departments->fetch_assoc()): 
                                ?>
                                    <label class="flex items-center gap-3 cursor-pointer p-1 hover:bg-gray-200 dark:hover:bg-warmdark-hover rounded transition-colors">
                                        <input type="checkbox" name="personnel_departments[]" value="<?= $d['id'] ?>" 
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="text-sm text-gray-700 dark:text-gray-200 font-medium"><?= htmlspecialchars($d['name']); ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 italic">* You can select multiple departments.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors shadow-md shadow-blue-600/20">
                        Create Account
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function registrationApp() {
            return {
                role: 'student', 
                password: '',
                confirm_password: '',
                errorMsg: '',
                personnelServiceRole: '',

                validateForm(e) {
                    this.errorMsg = '';
                    if (this.password !== this.confirm_password) {
                        e.preventDefault();
                        this.errorMsg = "Passwords do not match. Please try again.";
                        return;
                    }
                },

                fetchCourses(event) {
                    const departmentId = event.target.value;
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
                }
            }
        }
    </script>
</body>
</html>
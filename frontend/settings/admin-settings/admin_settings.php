<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

// 1. Fetch Admin Profile
$stmt = $conn->prepare("SELECT school_id, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Fetch System Settings (Active School Year)
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';

// 3. Fetch Departments and Courses for the new Dynamic Requirements UI
$departments = [];
$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
while ($d = $dept_query->fetch_assoc()) {
    $departments[] = $d;
}

$courses = [];
$course_query = $conn->query("SELECT id, department_id, name FROM courses ORDER BY name ASC");
while ($c = $course_query->fetch_assoc()) {
    $courses[] = $c;
}

// 4. UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024;
$current_calendar_year = (int)date("Y"); 
$max_year = $current_calendar_year + 2; 

$generated_years = [];
for ($y = $max_year; $y >= $start_year; $y--) {
    $next_y = $y + 1;
    $generated_years[] = $y . "-" . $next_y;
}
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 transition-colors duration-300" x-data="adminSettingsApp()">
    
    <!-- Header -->
    <div class="mb-8 pb-5 border-b border-gray-200 dark:border-warmdark-border flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Settings</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Manage your account settings, security, and global system configurations.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- ===================== SIDEBAR NAVIGATION ===================== -->
        <aside class="w-full lg:w-64 shrink-0">
            <nav class="flex flex-col gap-2">
                <!-- Profile Tab -->
                <button @click="activeTab = 'profile'" 
                        :class="activeTab === 'profile' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Admin Profile
                </button>
                
                <!-- Security Tab -->
                <button @click="activeTab = 'security'" 
                        :class="activeTab === 'security' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    Security & Password
                </button>

                <!-- System Tab -->
                <button @click="activeTab = 'system'" 
                        :class="activeTab === 'system' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Global System
                </button>

                <!-- Document Requirements Tab -->
                <button @click="activeTab = 'requirements'" 
                        :class="activeTab === 'requirements' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Document Req.
                </button>

                <!-- NEW: Required Services Tab -->
                <button @click="activeTab = 'course_services'" 
                        :class="activeTab === 'course_services' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    Course Services
                </button>

                <!-- Appearance Tab -->
                <button @click="activeTab = 'appearance'" 
                        :class="activeTab === 'appearance' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-warmdark-panel dark:hover:text-gray-200'"
                        class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all w-full text-left relative overflow-hidden group">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    Appearance
                </button>
            </nav>
        </aside>

        <!-- ===================== CONTENT AREA ===================== -->
        <main class="flex-1 min-w-0">
            
            <!-- ====== PROFILE TAB ====== -->
            <div x-show="activeTab === 'profile'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border flex items-center gap-5 bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-2xl font-bold shadow-lg ring-4 ring-white dark:ring-warmdark-panel">
                        A
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Admin Profile</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Manage your personal identification details.</p>
                    </div>
                </div>
                
                <div class="p-8">
                    <form @submit.prevent="updateProfile">
                        <div class="space-y-6 max-w-xl">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Admin Username / ID Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input type="text" x-model="profile.school_id" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Recovery Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input type="email" x-model="profile.email" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium">
                                </div>
                            </div>
                        </div>

                        <div x-show="profileMsg" x-collapse x-cloak>
                            <div class="mt-6 p-4 rounded-xl text-sm font-medium border flex items-center gap-3 max-w-xl" 
                                 :class="isProfileError ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:border-red-900/30' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-900/30'">
                                <span x-text="profileMsg"></span>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-warmdark-border flex justify-end max-w-xl">
                            <button type="submit" :disabled="isProfileLoading" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                <span x-show="!isProfileLoading">Save Profile</span>
                                <svg x-show="isProfileLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ====== SECURITY TAB ====== -->
            <div x-show="activeTab === 'security'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden" x-cloak>
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Security & Password</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Ensure your account is using a secure password.</p>
                </div>
                
                <div class="p-8">
                    <form @submit.prevent="updatePassword">
                        <div class="space-y-6 max-w-xl">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Current Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    </div>
                                    <input type="password" x-model="passwords.current" required placeholder="Enter current password"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">New Password</label>
                                    <input type="password" x-model="passwords.new" required minlength="6" placeholder="Minimum 6 characters"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Confirm New Password</label>
                                    <input type="password" x-model="passwords.confirm" required minlength="6" placeholder="Re-type new password"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium">
                                </div>
                            </div>
                        </div>

                        <div x-show="passMsg" x-collapse x-cloak>
                            <div class="mt-6 p-4 rounded-xl text-sm font-medium border flex items-center gap-3 max-w-xl" 
                                 :class="isPassError ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:border-red-900/30' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-900/30'">
                                <span x-text="passMsg"></span>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-warmdark-border flex justify-end max-w-xl">
                            <button type="submit" :disabled="isPassLoading" 
                                    class="bg-gray-900 hover:bg-gray-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                <span x-show="!isPassLoading">Update Password</span>
                                <svg x-show="isPassLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ====== SYSTEM TAB ====== -->
            <div x-show="activeTab === 'system'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden" x-cloak>
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Global System Settings</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Configure core operational settings like the active school year.</p>
                </div>
                
                <div class="p-8">
                    <form @submit.prevent="updateSystem">
                        <div class="max-w-md">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Active School Year</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">This controls the default school year assigned to new student accounts and sets the default filter on dashboards.</p>
                            
                            <div class="relative">
                                <select x-model="system.school_year" class="w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm bg-gray-50 dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none font-semibold transition-all shadow-sm appearance-none cursor-pointer">
                                    <?php foreach($generated_years as $year): ?>
                                        <option value="<?php echo $year; ?>">SY <?php echo $year; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div x-show="sysMsg" x-collapse x-cloak>
                            <div class="mt-6 p-4 rounded-xl text-sm font-medium border flex items-center gap-3 max-w-md" 
                                 :class="isSysError ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:border-red-900/30' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-900/30'">
                                <span x-text="sysMsg"></span>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-warmdark-border flex justify-start max-w-md">
                            <button type="submit" :disabled="isSysLoading" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                <span x-show="!isSysLoading">Apply Global Setting</span>
                                <svg x-show="isSysLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ====== DOCUMENT REQUIREMENTS TAB ====== -->
            <div x-show="activeTab === 'requirements'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden" x-cloak>
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Document Upload Requirements</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Configure dynamic lists of mandatory document text fields tailored to specific courses.</p>
                </div>
                
                <div class="p-8">
                    <!-- Step Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                        <div>
                            <label class="block text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider mb-2">1. Select Service</label>
                            <select x-model="reqFilters.service" @change="fetchCourseRequirements" class="w-full border border-white dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/20 focus:outline-none font-semibold transition-colors shadow-sm cursor-pointer">
                                <option value="">-- Choose Service --</option>
                                <option value="Grammarly & AI Checking">Grammarly & AI Checking</option>
                                <option value="Librarian">Librarian</option>
                                <option value="Human Grammarian">Human Grammarian</option>
                                <option value="Ethics">Ethics</option>
                                <option value="Statistician">Statistician</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider mb-2">2. Select Department</label>
                            <select x-model="reqFilters.dept" @change="updateCoursesList" class="w-full border border-white dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/20 focus:outline-none font-semibold transition-colors shadow-sm cursor-pointer">
                                <option value="">-- Choose Department --</option>
                                <template x-for="dept in departments" :key="dept.id">
                                    <option :value="dept.id" x-text="dept.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider mb-2">3. Select Course</label>
                            <select x-model="reqFilters.course" @change="fetchCourseRequirements" :disabled="!reqFilters.dept" class="w-full border border-white dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/20 focus:outline-none font-semibold transition-colors shadow-sm disabled:opacity-50 cursor-pointer">
                                <option value="">-- Choose Course --</option>
                                <option value="all" class="font-bold text-blue-600 dark:text-blue-400">Apply to ALL Courses</option>
                                
                                <template x-for="course in filteredCourses" :key="course.id">
                                    <option :value="course.id" x-text="course.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Editor -->
                    <div x-show="reqFilters.service && reqFilters.course" x-collapse x-cloak>
                        
                        <!-- Warning Banner for "Select All" -->
                        <div x-show="reqFilters.course === 'all'" class="mb-5 bg-amber-50 dark:bg-yellow-900/20 border border-amber-200 dark:border-yellow-900/50 p-4 rounded-xl flex items-center gap-3">
                            <svg class="w-6 h-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-400">
                                <strong>Batch Overwrite:</strong> You are about to apply these requirements to <strong x-text="filteredCourses.length"></strong> courses. This will overwrite any existing individual requirements for those courses.
                            </p>
                        </div>

                        <form @submit.prevent="updateRequirements" class="bg-gray-50 dark:bg-warmdark-bg p-8 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors shadow-inner">
                            
                            <h3 class="text-base font-bold text-gray-800 dark:text-gray-200 mb-6 pb-3 border-b border-gray-200 dark:border-warmdark-border flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Document Requirements for <span class="text-blue-600 dark:text-blue-400" x-text="reqFilters.service"></span>
                            </h3>
                            
                            <div class="space-y-3">
                                <template x-for="(req, index) in activeRequirementsList" :key="index">
                                    <div class="flex items-center gap-3 group">
                                        <div class="w-8 h-8 rounded-full bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-500 dark:text-gray-400 flex items-center justify-center text-xs font-bold shrink-0 shadow-sm" x-text="index + 1"></div>
                                        <input type="text" x-model="activeRequirementsList[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 shadow-sm font-medium" placeholder="e.g., Final Edited Manuscript (PDF)">
                                        <button type="button" @click="activeRequirementsList.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-3 rounded-xl border border-gray-200 dark:border-warmdark-border shadow-sm opacity-50 group-hover:opacity-100 hover:bg-red-50 dark:hover:bg-red-900/20 dark:hover:border-red-900/30" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            
                            <button type="button" @click="activeRequirementsList.push('')" class="mt-4 ml-11 px-4 py-2 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-sm text-gray-600 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-warmdark-hover rounded-xl shadow-sm flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Document Field
                            </button>

                            <div x-show="reqMsg" class="mt-6 p-4 rounded-xl text-sm font-medium border flex items-center gap-3" :class="isReqError ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:border-red-900/30' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-900/30'" x-cloak>
                                <span x-text="reqMsg"></span>
                            </div>

                            <div class="pt-8 mt-8 border-t border-gray-200 dark:border-warmdark-border flex justify-end">
                                <button type="submit" :disabled="isReqLoading" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-8 rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                    <span x-show="!isReqLoading" x-text="reqFilters.course === 'all' ? 'Save to All Courses' : 'Save Requirements'"></span>
                                    <svg x-show="isReqLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div x-show="!reqFilters.service || !reqFilters.course" class="text-center py-16 bg-gray-50 dark:bg-warmdark-bg rounded-2xl border border-dashed border-gray-300 dark:border-warmdark-border">
                        <div class="w-16 h-16 rounded-full bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-400 dark:text-gray-500 mx-auto flex items-center justify-center mb-4 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Select a Service, Department, and Course above to configure requirements.</p>
                    </div>
                </div>
            </div>

            <!-- ====== NEW: COURSE SERVICES TAB ====== -->
            <div x-show="activeTab === 'course_services'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden" x-cloak>
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Course Required Services</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Toggle which research services are mandatory for a specific course.</p>
                </div>
                
                <div class="p-8">
                    <!-- Step Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8 bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                        <div>
                            <label class="block text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider mb-2">1. Select Department</label>
                            <select x-model="csFilters.dept" @change="updateCSCoursesList" class="w-full border border-white dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/20 focus:outline-none font-semibold transition-colors shadow-sm cursor-pointer">
                                <option value="">-- Choose Department --</option>
                                <template x-for="dept in departments" :key="dept.id">
                                    <option :value="dept.id" x-text="dept.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider mb-2">2. Select Course</label>
                            <select x-model="csFilters.course" @change="fetchCourseServices" :disabled="!csFilters.dept" class="w-full border border-white dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 focus:ring-4 focus:ring-blue-500/20 focus:outline-none font-semibold transition-colors shadow-sm disabled:opacity-50 cursor-pointer">
                                <option value="">-- Choose Course --</option>
                                <option value="all" class="font-bold text-blue-600 dark:text-blue-400">Apply to ALL Courses</option>
                                
                                <template x-for="course in csFilteredCourses" :key="course.id">
                                    <option :value="course.id" x-text="course.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Editor -->
                    <div x-show="csFilters.dept && csFilters.course" x-collapse x-cloak>
                        
                        <div x-show="csFilters.course === 'all'" class="mb-5 bg-amber-50 dark:bg-yellow-900/20 border border-amber-200 dark:border-yellow-900/50 p-4 rounded-xl flex items-center gap-3">
                            <svg class="w-6 h-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-400">
                                <strong>Batch Overwrite:</strong> You are about to apply these service settings to <strong x-text="csFilteredCourses.length"></strong> courses. 
                            </p>
                        </div>

                        <form @submit.prevent="saveCourseServices" class="bg-gray-50 dark:bg-warmdark-bg p-8 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors shadow-inner">
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-gray-200 dark:border-warmdark-border">
                                <h3 class="text-base font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                    Required Services
                                </h3>
                                <div class="flex gap-2">
                                    <button type="button" @click="checkAllCS()" class="px-3 py-1.5 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-xs font-bold text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-warmdark-hover shadow-sm transition">Check All</button>
                                    <button type="button" @click="uncheckAllCS()" class="px-3 py-1.5 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-xs font-bold text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-warmdark-hover shadow-sm transition">Uncheck All</button>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="flex items-center gap-3 p-4 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-800 transition shadow-sm">
                                    <input type="checkbox" x-model="csData.req_grammarly_ai" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Grammarly & AI Checking</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-800 transition shadow-sm">
                                    <input type="checkbox" x-model="csData.req_ethics" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Ethics</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-800 transition shadow-sm">
                                    <input type="checkbox" x-model="csData.req_human_grammarian" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Human Grammarian</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-800 transition shadow-sm">
                                    <input type="checkbox" x-model="csData.req_librarian" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Librarian</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-800 transition shadow-sm">
                                    <input type="checkbox" x-model="csData.req_statistician" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 bg-white dark:bg-warmdark-bg">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Statistician</span>
                                </label>
                            </div>

                            <div x-show="csMsg" class="mt-6 p-4 rounded-xl text-sm font-medium border flex items-center gap-3" :class="isCSError ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:border-red-900/30' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-900/30'" x-cloak>
                                <span x-text="csMsg"></span>
                            </div>

                            <div class="pt-8 mt-8 border-t border-gray-200 dark:border-warmdark-border flex justify-end">
                                <button type="submit" :disabled="isCSLoading" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-8 rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                                    <span x-show="!isCSLoading" x-text="csFilters.course === 'all' ? 'Save to All Courses' : 'Save Required Services'"></span>
                                    <svg x-show="isCSLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div x-show="!csFilters.dept || !csFilters.course" class="text-center py-16 bg-gray-50 dark:bg-warmdark-bg rounded-2xl border border-dashed border-gray-300 dark:border-warmdark-border">
                        <div class="w-16 h-16 rounded-full bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-400 dark:text-gray-500 mx-auto flex items-center justify-center mb-4 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Select a Department and Course above to toggle required services.</p>
                    </div>
                </div>
            </div>

            <!-- ====== APPEARANCE TAB ====== -->
            <div x-show="activeTab === 'appearance'" x-transition.opacity.duration.300ms class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden" x-cloak>
                <div class="px-8 py-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Appearance</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Customize the interface theme. This setting is saved locally on your browser.</p>
                </div>
                <div class="p-8">
                    <div class="max-w-xl">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Select Theme</label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div @click="theme = 'light'; updateTheme()" 
                                 class="cursor-pointer border-2 rounded-2xl p-5 flex flex-col items-center gap-3 transition-all"
                                 :class="theme === 'light' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-warmdark-border hover:border-blue-300'">
                                <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <span class="font-bold text-gray-800 dark:text-gray-200">Light Mode</span>
                            </div>
                            
                            <div @click="theme = 'dark'; updateTheme()" 
                                 class="cursor-pointer border-2 rounded-2xl p-5 flex flex-col items-center gap-3 transition-all"
                                 :class="theme === 'dark' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-warmdark-border hover:border-blue-300'">
                                <div class="w-12 h-12 rounded-full bg-gray-800 text-blue-400 flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                </div>
                                <span class="font-bold text-gray-800 dark:text-gray-200">Dark Mode</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
function adminSettingsApp() {
    return {
        activeTab: localStorage.getItem('adminSettingsTab') || 'profile',
        
        init() {
            this.$watch('activeTab', value => localStorage.setItem('adminSettingsTab', value));
        },

        theme: localStorage.getItem('theme') || 'light',

        system: { school_year: <?php echo json_encode($active_sy); ?> },
        isSysLoading: false, sysMsg: '', isSysError: false,

        departments: <?php echo json_encode($departments); ?>,
        allCourses: <?php echo json_encode($courses); ?>,

        // Original Requirements Config
        filteredCourses: [],
        reqFilters: { service: '', dept: '', course: '' },
        activeRequirementsList: [],
        isReqLoading: false, reqMsg: '', isReqError: false,

        // NEW: Course Services Config
        csFilteredCourses: [],
        csFilters: { dept: '', course: '' },
        csData: { req_grammarly_ai: true, req_ethics: true, req_human_grammarian: true, req_librarian: true, req_statistician: true },
        isCSLoading: false, csMsg: '', isCSError: false,

        // Admin Profile
        profile: {
            school_id: <?php echo json_encode($admin['school_id'] ?? ''); ?>,
            email: <?php echo json_encode($admin['email'] ?? ''); ?>
        },
        isProfileLoading: false, profileMsg: '', isProfileError: false,

        // Password Data
        passwords: { current: '', new: '', confirm: '' },
        isPassLoading: false, passMsg: '', isPassError: false,

        // --- Methods ---
        updateTheme() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        },

        updateSystem() {
            this.isSysLoading = true; this.sysMsg = '';
            fetch('../../backend/ajax/update_system_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ active_school_year: this.system.school_year })
            })
            .then(res => res.json())
            .then(data => {
                this.isSysLoading = false;
                this.isSysError = !data.success;
                this.sysMsg = data.message;
                if(data.success) {
                    setTimeout(() => { window.location.reload(); }, 1500); 
                }
            })
            .catch(err => {
                this.isSysLoading = false; this.isSysError = true; this.sysMsg = "Connection error.";
            });
        },

        updateCoursesList() {
            this.filteredCourses = this.allCourses.filter(c => c.department_id == this.reqFilters.dept);
            this.reqFilters.course = '';
            this.activeRequirementsList = [];
        },

        fetchCourseRequirements() {
            if (!this.reqFilters.service || !this.reqFilters.course) {
                this.activeRequirementsList = [];
                return;
            }

            let fetchCourseId = this.reqFilters.course === 'all' 
                ? (this.filteredCourses[0] ? this.filteredCourses[0].id : 0) 
                : this.reqFilters.course;

            if(!fetchCourseId) {
                this.activeRequirementsList = [''];
                return;
            }

            fetch(`../../backend/ajax/fetch_specific_requirements.php?service=${encodeURIComponent(this.reqFilters.service)}&course_id=${fetchCourseId}`)
            .then(res => res.json())
            .then(data => {
                if(data && data.length > 0) {
                    this.activeRequirementsList = data;
                } else {
                    this.activeRequirementsList = ['']; 
                }
            })
            .catch(err => {
                console.error("Failed to load requirements", err);
                this.activeRequirementsList = [''];
            });
        },

        updateRequirements() {
            this.isReqLoading = true; 
            this.reqMsg = '';

            let hasEmpty = this.activeRequirementsList.some(val => val.trim() === '');
            if (hasEmpty) {
                this.isReqLoading = false;
                this.isReqError = true;
                this.reqMsg = "Please fill in all requirement fields or remove the empty ones before saving.";
                return;
            }

            let validReqs = this.activeRequirementsList.filter(val => val.trim() !== '');

            let courseIdsToUpdate = this.reqFilters.course === 'all'
                ? this.filteredCourses.map(c => c.id)
                : [this.reqFilters.course];

            if(courseIdsToUpdate.length === 0) {
                this.isReqLoading = false;
                this.isReqError = true;
                this.reqMsg = "No courses found in this department.";
                return;
            }

            let promises = courseIdsToUpdate.map(cid => {
                return fetch('../../backend/ajax/update_specific_requirements.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        service: this.reqFilters.service,
                        course_id: cid,
                        requirements: validReqs
                    })
                }).then(res => res.json());
            });

            Promise.all(promises)
            .then(results => {
                this.isReqLoading = false;
                
                let hasError = results.some(r => !r.success);
                this.isReqError = hasError;
                
                if (hasError) {
                    this.reqMsg = "Failed to save to some courses. Please try again.";
                } else {
                    let msgTarget = this.reqFilters.course === 'all' ? `ALL ${courseIdsToUpdate.length} courses` : 'the course';
                    this.reqMsg = `Requirements successfully saved to ${msgTarget}!`;
                    setTimeout(() => { this.reqMsg = ''; }, 4000); 
                }
            })
            .catch(err => {
                this.isReqLoading = false; this.isReqError = true; this.reqMsg = "Connection error while saving.";
            });
        },

        // --- NEW: Course Services Methods ---
        updateCSCoursesList() {
            this.csFilteredCourses = this.allCourses.filter(c => c.department_id == this.csFilters.dept);
            this.csFilters.course = '';
        },

        fetchCourseServices() {
            if (!this.csFilters.course) return;

            // If "Apply to ALL" is selected, just keep current selections.
            if (this.csFilters.course === 'all') return;

            fetch(`../../backend/ajax/fetch_course_services.php?course_id=${this.csFilters.course}`)
            .then(res => res.json())
            .then(data => {
                if (data && !data.error) {
                    this.csData.req_grammarly_ai = parseInt(data.req_grammarly_ai) === 1;
                    this.csData.req_ethics = parseInt(data.req_ethics) === 1;
                    this.csData.req_human_grammarian = parseInt(data.req_human_grammarian) === 1;
                    this.csData.req_librarian = parseInt(data.req_librarian) === 1;
                    this.csData.req_statistician = parseInt(data.req_statistician) === 1;
                }
            })
            .catch(err => console.error("Failed to load course services", err));
        },

        checkAllCS() {
            for (let key in this.csData) this.csData[key] = true;
        },

        uncheckAllCS() {
            for (let key in this.csData) this.csData[key] = false;
        },

        saveCourseServices() {
            this.isCSLoading = true;
            this.csMsg = '';

            let courseIdsToUpdate = this.csFilters.course === 'all'
                ? this.csFilteredCourses.map(c => c.id)
                : [this.csFilters.course];

            if(courseIdsToUpdate.length === 0) {
                this.isCSLoading = false;
                this.isCSError = true;
                this.csMsg = "No courses found to update.";
                return;
            }

            let promises = courseIdsToUpdate.map(cid => {
                return fetch('../../backend/ajax/update_course_services.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        course_id: cid,
                        req_grammarly_ai: this.csData.req_grammarly_ai ? 1 : 0,
                        req_ethics: this.csData.req_ethics ? 1 : 0,
                        req_human_grammarian: this.csData.req_human_grammarian ? 1 : 0,
                        req_librarian: this.csData.req_librarian ? 1 : 0,
                        req_statistician: this.csData.req_statistician ? 1 : 0
                    })
                }).then(res => res.json());
            });

            Promise.all(promises)
            .then(results => {
                this.isCSLoading = false;
                let hasError = results.some(r => !r.success);
                this.isCSError = hasError;
                
                if (hasError) {
                    this.csMsg = "Failed to update some courses.";
                } else {
                    let msgTarget = this.csFilters.course === 'all' ? `ALL ${courseIdsToUpdate.length} courses` : 'the course';
                    this.csMsg = `Required services successfully saved for ${msgTarget}!`;
                    setTimeout(() => { this.csMsg = ''; }, 4000); 
                }
            })
            .catch(err => {
                this.isCSLoading = false; this.isCSError = true; this.csMsg = "Connection error.";
            });
        },

        // --- Original Methods ---
        updateProfile() {
            this.isProfileLoading = true; this.profileMsg = '';
            fetch('../../backend/ajax/update_admin_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.profile)
            })
            .then(res => res.json())
            .then(data => {
                this.isProfileLoading = false;
                this.isProfileError = !data.success;
                this.profileMsg = data.message;
                if(data.success) setTimeout(() => { window.location.reload(); }, 1500);
            })
            .catch(err => {
                this.isProfileLoading = false; this.isProfileError = true; this.profileMsg = "Connection error.";
            });
        },

        updatePassword() {
            this.passMsg = '';
            if (this.passwords.new !== this.passwords.confirm) {
                this.isPassError = true; this.passMsg = "New passwords do not match."; return;
            }
            this.isPassLoading = true;
            fetch('../../backend/ajax/update_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: this.passwords.current, new_password: this.passwords.new })
            })
            .then(res => res.json())
            .then(data => {
                this.isPassLoading = false;
                this.isPassError = !data.success;
                this.passMsg = data.message;
                if (data.success) {
                    this.passwords = { current: '', new: '', confirm: '' };
                    setTimeout(() => { this.passMsg = ''; }, 4000);
                }
            })
            .catch(err => {
                this.isPassLoading = false; this.isPassError = true; this.passMsg = "Connection error.";
            });
        }
    }
}
</script>
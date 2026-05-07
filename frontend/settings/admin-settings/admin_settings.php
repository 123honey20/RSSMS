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

// 3. Fetch Service Requirements Text and Parse as Arrays
$req_keys = ['req_desc_grammarly_ai', 'req_desc_ethics', 'req_desc_human_grammarian', 'req_desc_librarian', 'req_desc_statistician'];
$req_texts = array_fill_keys($req_keys, '');
$req_query = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('" . implode("','", $req_keys) . "')");
while ($row = $req_query->fetch_assoc()) {
    $req_texts[$row['setting_key']] = $row['setting_value'];
}

$parsed_reqs = [];
foreach ($req_texts as $key => $val) {
    $arr = json_decode($val, true);
    if (!is_array($arr)) {
        // Fallback: If it's legacy plain text, split by new lines
        $arr = array_filter(explode("\n", trim($val)));
    }
    if (empty($arr)) {
        $arr = ['']; // Ensure there is always at least one blank input field
    }
    $parsed_reqs[$key] = array_values($arr);
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

// 5. DYNAMIC CERTIFICATE CHECKING
$cert_dir = "../../images/certificates/proposal-certificate/";
$current_cert_path = "";
$current_cert_type = "";

if (file_exists($cert_dir . "Proposal_Certificate.pdf")) {
    $current_cert_path = $cert_dir . "Proposal_Certificate.pdf?v=" . time();
    $current_cert_type = "pdf";
} elseif (file_exists($cert_dir . "Proposal_Certificate.png")) {
    $current_cert_path = $cert_dir . "Proposal_Certificate.png?v=" . time();
    $current_cert_type = "image";
} elseif (file_exists($cert_dir . "Proposal_Certificate.jpg")) {
    $current_cert_path = $cert_dir . "Proposal_Certificate.jpg?v=" . time();
    $current_cert_type = "image";
}
?>

<div class="max-w-6xl mx-auto py-6 px-4 transition-colors duration-200" x-data="adminSettingsApp()">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">System Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage global system configurations and your admin credentials.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- MAIN SECTION (Left Side, Takes 2 columns) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- ADMIN PROFILE CARD -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <!-- Card Header with Avatar -->
                <div class="p-8 border-b border-gray-100 dark:border-warmdark-border flex flex-col sm:flex-row items-center sm:items-start gap-6 relative">
                    <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-warmdark-bg opacity-10"></div>
                    
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center text-white text-3xl font-bold shadow-lg ring-4 ring-white dark:ring-warmdark-panel z-10">
                        A
                    </div>
                    
                    <div class="text-center sm:text-left z-10 pt-2">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">System Administrator</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($admin['email'] ?? 'No Email') ?></p>
                        <span class="inline-block mt-3 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wider rounded-full border border-blue-100 dark:border-blue-800/50">
                            Admin Account
                        </span>
                    </div>
                </div>

                <!-- Form Body -->
                <div class="p-8">
                    <form @submit.prevent="updateProfile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username / ID -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Admin Username / ID Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input type="text" x-model="profile.school_id" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Recovery Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input type="email" x-model="profile.email" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Profile Alert Message -->
                        <div x-show="profileMsg" x-collapse x-cloak>
                            <div class="mt-5 p-4 rounded-xl text-sm font-medium border flex items-center gap-3" 
                                 :class="isProfileError ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/30'">
                                <svg x-show="!isProfileError" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <svg x-show="isProfileError" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span x-text="profileMsg"></span>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" :disabled="isProfileLoading" 
                                    class="bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg x-show="!isProfileLoading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                <span x-show="!isProfileLoading">Save Credentials</span>
                                <svg x-show="isProfileLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="isProfileLoading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password & Authentication Trigger Section -->
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 dark:bg-warmdark-bg dark:border-warmdark-border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Password & Authentication</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Want to update your admin security credentials? Change your password here.</p>
                    </div>
                    <button type="button" @click="openPassModal" class="shrink-0 bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-warmdark-hover font-semibold py-2 px-5 rounded-lg text-sm shadow-sm transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Change Password
                    </button>
                </div>
            </div>

            <!-- GLOBAL SYSTEM SETTINGS CARD -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <div class="p-6 border-b border-gray-100 dark:border-warmdark-border flex items-center gap-3 bg-blue-50/30 dark:bg-blue-900/10">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <h2 class="text-base font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider">Global System Settings</h2>
                </div>
                <div class="p-8">
                    <form @submit.prevent="updateSystem">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Active School Year</label>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">This controls the default school year assigned to new students and filters the dashboards.</p>
                            <select x-model="system.school_year" class="w-full md:w-1/2 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors bg-white dark:bg-warmdark-bg font-semibold text-gray-800 dark:text-gray-200">
                                <?php foreach($generated_years as $year): ?>
                                    <option value="<?php echo $year; ?>">SY <?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div x-show="sysMsg" class="mt-4 p-3 rounded-xl text-sm font-medium border" :class="isSysError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                            <span x-text="sysMsg"></span>
                        </div>

                        <div class="mt-6 flex justify-start">
                            <button type="submit" :disabled="isSysLoading" class="bg-blue-600 dark:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-xl hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50 text-sm shadow-sm flex items-center gap-2">
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

            <!-- SERVICE UPLOAD REQUIREMENTS CARD -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <div class="p-6 border-b border-gray-100 dark:border-warmdark-border flex items-center gap-3 bg-emerald-50/50 dark:bg-emerald-900/10">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-500 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h2 class="text-base font-bold text-emerald-900 dark:text-emerald-500 uppercase tracking-wider">Service Upload Requirements</h2>
                </div>
                <div class="p-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Create a dynamic list of documents the students need to upload for each research service.</p>
                    
                    <form @submit.prevent="updateRequirements" class="space-y-6">
                        
                        <!-- Grammarly Requirements -->
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-5 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-4">Grammarly & AI Checking Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_grammarly_ai" :key="index">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-sm font-bold text-gray-400 w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_grammarly_ai[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_grammarly_ai.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-2 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_grammarly_ai.push('')" class="text-sm text-emerald-600 dark:text-emerald-500 font-bold hover:text-emerald-700 dark:hover:text-emerald-400 ml-8 mt-2 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <!-- Ethics Requirements -->
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-5 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-4">Ethics Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_ethics" :key="index">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-sm font-bold text-gray-400 w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_ethics[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_ethics.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-2 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_ethics.push('')" class="text-sm text-emerald-600 dark:text-emerald-500 font-bold hover:text-emerald-700 dark:hover:text-emerald-400 ml-8 mt-2 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <!-- Human Grammarian Requirements -->
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-5 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-4">Human Grammarian Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_human_grammarian" :key="index">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-sm font-bold text-gray-400 w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_human_grammarian[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_human_grammarian.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-2 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_human_grammarian.push('')" class="text-sm text-emerald-600 dark:text-emerald-500 font-bold hover:text-emerald-700 dark:hover:text-emerald-400 ml-8 mt-2 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <!-- Librarian Requirements -->
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-5 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-4">Librarian Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_librarian" :key="index">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-sm font-bold text-gray-400 w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_librarian[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_librarian.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-2 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_librarian.push('')" class="text-sm text-emerald-600 dark:text-emerald-500 font-bold hover:text-emerald-700 dark:hover:text-emerald-400 ml-8 mt-2 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <!-- Statistician Requirements -->
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-5 rounded-2xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-4">Statistician Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_statistician" :key="index">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-sm font-bold text-gray-400 w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_statistician[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_statistician.splice(index, 1)" class="text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-warmdark-panel p-2 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_statistician.push('')" class="text-sm text-emerald-600 dark:text-emerald-500 font-bold hover:text-emerald-700 dark:hover:text-emerald-400 ml-8 mt-2 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div x-show="reqMsg" class="mt-4 p-4 rounded-xl text-sm font-medium border flex items-center gap-3" :class="isReqError ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/30'" x-cloak>
                            <svg x-show="!isReqError" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg x-show="isReqError" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span x-text="reqMsg"></span>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" :disabled="isReqLoading" class="bg-emerald-600 dark:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors disabled:opacity-50 text-sm shadow-sm flex items-center gap-2">
                                <svg x-show="!isReqLoading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                <span x-show="!isReqLoading">Save Requirements</span>
                                <svg x-show="isReqLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="isReqLoading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- SIDEBAR SECTION (Right Side, Takes 1 column) -->
        <div class="lg:col-span-1 space-y-8">
            
            <!-- APPEARANCE CARD -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100 tracking-wide">Appearance</h2>
                </div>
                <div class="p-6">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Theme Preference</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">Choose how the dashboard looks. This setting is saved locally in your current browser.</p>
                    
                    <div class="relative">
                        <select x-model="theme" @change="updateTheme" class="w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors bg-gray-50 dark:bg-warmdark-bg font-semibold text-gray-800 dark:text-gray-200 appearance-none cursor-pointer">
                            <option value="light">☀️ Light Mode</option>
                            <option value="dark">🌙 Dark Mode (Warm)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROPOSAL CERTIFICATE CARD -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border flex items-center gap-3 bg-amber-50/50 dark:bg-yellow-900/10">
                    <div class="p-2 bg-amber-100 dark:bg-yellow-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h2 class="text-sm font-bold text-amber-900 dark:text-yellow-500 uppercase tracking-wider">Proposal Certificate</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateCertificate">
                        <div class="flex flex-col gap-5">
                            
                            <div class="w-full flex flex-col items-center">
                                <span class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 self-start">Current Template</span>
                                
                                <div class="w-full aspect-[1/1.4] bg-gray-50 dark:bg-warmdark-bg border-2 border-dashed border-gray-300 dark:border-warmdark-border rounded-xl overflow-hidden relative flex justify-center items-center group transition-all">
                                    <template x-if="certPreview && certFileType === 'image'">
                                        <img :src="certPreview" class="w-full h-full object-contain absolute inset-0 bg-white dark:bg-warmdark-bg">
                                    </template>
                                    <template x-if="certPreview && certFileType === 'pdf'">
                                        <iframe :src="certPreview" class="w-full h-full absolute inset-0 bg-white dark:bg-warmdark-bg" frameborder="0"></iframe>
                                    </template>

                                    <template x-if="!certPreview && currentCertType === 'image'">
                                        <img :src="currentCertPath" class="w-full h-full object-contain absolute inset-0 bg-white dark:bg-warmdark-bg" onerror="this.style.display='none'">
                                    </template>
                                    <template x-if="!certPreview && currentCertType === 'pdf'">
                                        <iframe :src="currentCertPath" class="w-full h-full absolute inset-0 bg-white dark:bg-warmdark-bg" frameborder="0"></iframe>
                                    </template>

                                    <template x-if="!certPreview && currentCertType === ''">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            <span class="text-gray-400 dark:text-gray-500 text-sm font-semibold">No File Found</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="w-full flex flex-col justify-center border-t border-gray-100 dark:border-warmdark-border pt-5">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">Upload New File</label>
                                <p class="text-[11px] text-gray-500 dark:text-gray-500 mb-3 leading-relaxed">High-quality PNG, JPG, or PDF.</p>
                                
                                <input type="file" id="certFile" accept="image/png, image/jpeg, image/jpg, application/pdf" @change="handleFileChange" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-wider file:bg-amber-50 dark:file:bg-yellow-900/30 file:text-amber-700 dark:file:text-yellow-500 hover:file:bg-amber-100 dark:hover:file:bg-yellow-900/50 transition-colors border border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-bg rounded-xl mb-4 cursor-pointer">

                                <div x-show="certMsg" class="mb-4 p-3 rounded-xl text-sm font-medium border" :class="isCertError ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900/30'" x-cloak>
                                    <span x-text="certMsg"></span>
                                </div>

                                <div>
                                    <button type="submit" :disabled="!certFile || isCertLoading" class="w-full bg-amber-600 dark:bg-yellow-600 text-white font-bold py-2.5 rounded-xl hover:bg-amber-700 dark:hover:bg-yellow-500 transition-colors disabled:opacity-50 text-sm shadow-sm flex items-center justify-center gap-2">
                                        <svg x-show="!isCertLoading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                        <span x-show="!isCertLoading">Replace Template</span>
                                        <svg x-show="isCertLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-show="isCertLoading" x-cloak>Uploading...</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- PASSWORD CHANGE MODAL -->
    <!-- ========================================== -->
    <div x-show="isPassModalOpen" 
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
         
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="isPassModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm" aria-hidden="true" @click="closePassModal"></div>

            <!-- Modal Panel -->
            <div x-show="isPassModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="relative inline-block w-full max-w-md p-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-warmdark-panel rounded-2xl shadow-2xl border border-transparent dark:border-warmdark-border sm:my-8 sm:align-middle">
                
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100" id="modal-title">Change Password</h3>
                    <button @click="closePassModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="updatePassword">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" x-model="passwords.current" required placeholder="Enter current password"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" x-model="passwords.new" required minlength="6" placeholder="Minimum 6 characters"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" x-model="passwords.confirm" required minlength="6" placeholder="Re-type new password"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                        </div>
                    </div>

                    <div x-show="passMsg" x-collapse x-cloak class="mt-4">
                        <div class="p-3 rounded-xl text-sm font-medium border flex items-start gap-2" 
                             :class="isPassError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'">
                            <span x-text="passMsg"></span>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3">
                        <button type="button" @click="closePassModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isPassLoading" 
                                class="bg-gray-900 dark:bg-blue-600 hover:bg-gray-800 dark:hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
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
    </div>
</div>

<script>
function adminSettingsApp() {
    return {
        // Theme Config
        theme: localStorage.getItem('theme') || 'light',

        // System Config
        system: { school_year: <?php echo json_encode($active_sy); ?> },
        isSysLoading: false, sysMsg: '', isSysError: false,

        // Requirements Config 
        requirements: <?php echo json_encode($parsed_reqs); ?>,
        isReqLoading: false, reqMsg: '', isReqError: false,

        // Certificate Upload
        currentCertPath: <?php echo json_encode($current_cert_path); ?>,
        currentCertType: <?php echo json_encode($current_cert_type); ?>,
        certFile: null, 
        certPreview: null,
        certFileType: null,
        isCertLoading: false, certMsg: '', isCertError: false,

        // Admin Profile
        profile: {
            school_id: <?php echo json_encode($admin['school_id'] ?? ''); ?>,
            email: <?php echo json_encode($admin['email'] ?? ''); ?>
        },
        isProfileLoading: false, profileMsg: '', isProfileError: false,

        // Password & Modal
        isPassModalOpen: false,
        passwords: { current: '', new: '', confirm: '' },
        isPassLoading: false, passMsg: '', isPassError: false,

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

        updateRequirements() {
            this.isReqLoading = true; this.reqMsg = '';
            
            let payload = {};
            for (let key in this.requirements) {
                let validReqs = this.requirements[key].filter(val => val.trim() !== '');
                payload[key] = JSON.stringify(validReqs);
            }

            fetch('../../backend/ajax/update_requirements.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isReqLoading = false;
                this.isReqError = !data.success;
                this.reqMsg = data.message;
                if(data.success) {
                    setTimeout(() => { this.reqMsg = ''; }, 3000); 
                }
            })
            .catch(err => {
                this.isReqLoading = false; this.isReqError = true; this.reqMsg = "Connection error.";
            });
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.certFile = file;
                this.certPreview = URL.createObjectURL(file);
                this.certFileType = file.type === 'application/pdf' ? 'pdf' : 'image';
                this.certMsg = ''; 
            }
        },

        updateCertificate() {
            if (!this.certFile) return;

            this.isCertLoading = true; 
            this.certMsg = '';

            let formData = new FormData();
            formData.append("certificate", this.certFile);

            fetch('../../backend/ajax/update_certificate.php', {
                method: 'POST',
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                this.isCertLoading = false;
                this.isCertError = !data.success;
                this.certMsg = data.message;
                if(data.success) {
                    setTimeout(() => { window.location.reload(true); }, 1500);
                }
            })
            .catch(err => {
                this.isCertLoading = false; this.isCertError = true; this.certMsg = "Connection error.";
            });
        },

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

        openPassModal() {
            this.isPassModalOpen = true;
            this.passMsg = '';
            this.passwords = { current: '', new: '', confirm: '' };
        },

        closePassModal() {
            this.isPassModalOpen = false;
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
                    setTimeout(() => { this.closePassModal(); }, 1500);
                }
            })
            .catch(err => {
                this.isPassLoading = false; this.isPassError = true; this.passMsg = "Connection error.";
            });
        }
    }
}
</script>
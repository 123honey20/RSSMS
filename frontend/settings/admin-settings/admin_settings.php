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

<div class="max-w-5xl mx-auto pb-10" x-data="adminSettingsApp()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">System Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage global system configurations and your admin credentials.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Appearance</h2>
                </div>
                <div class="p-6">
                    <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Theme Preference</label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">Choose how the dashboard looks. This setting is saved in your browser.</p>
                    
                    <select x-model="theme" @change="updateTheme" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white dark:bg-warmdark-bg font-medium text-gray-700 dark:text-gray-200">
                        <option value="light">Light Mode</option>
                        <option value="dark">Dark Mode (Warm)</option>
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-blue-50/50 dark:bg-blue-900/10 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <h2 class="text-sm font-bold text-blue-900 dark:text-blue-400 uppercase tracking-wider">Global System Settings</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateSystem">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Active School Year</label>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">This controls the default school year assigned to new students and filters the dashboards.</p>
                            <select x-model="system.school_year" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white dark:bg-warmdark-bg font-medium text-gray-700 dark:text-gray-200">
                                <?php foreach($generated_years as $year): ?>
                                    <option value="<?php echo $year; ?>">SY <?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div x-show="sysMsg" class="mt-4 p-3 rounded-lg text-sm font-medium border" :class="isSysError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                            <span x-text="sysMsg"></span>
                        </div>

                        <div class="mt-5">
                            <button type="submit" :disabled="isSysLoading" class="bg-blue-600 dark:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isSysLoading">Apply Global Setting</span>
                                <span x-show="isSysLoading" x-cloak>Applying...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors mt-6">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-emerald-50/50 dark:bg-emerald-900/10 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <h2 class="text-sm font-bold text-emerald-900 dark:text-emerald-500 uppercase tracking-wider">Service Upload Requirements</h2>
                </div>
                <div class="p-6">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Create a dynamic list of documents the students need to upload for each research services.</p>
                    
                    <form @submit.prevent="updateRequirements" class="space-y-6">
                        
                        <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">Grammarly & AI Checking Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_grammarly_ai" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 w-4 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_grammarly_ai[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_grammarly_ai.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_grammarly_ai.push('')" class="text-xs text-emerald-600 dark:text-emerald-500 font-bold hover:underline ml-6 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">Ethics Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_ethics" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 w-4 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_ethics[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_ethics.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_ethics.push('')" class="text-xs text-emerald-600 dark:text-emerald-500 font-bold hover:underline ml-6 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">Human Grammarian Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_human_grammarian" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 w-4 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_human_grammarian[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_human_grammarian.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_human_grammarian.push('')" class="text-xs text-emerald-600 dark:text-emerald-500 font-bold hover:underline ml-6 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">Librarian Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_librarian" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 w-4 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_librarian[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_librarian.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_librarian.push('')" class="text-xs text-emerald-600 dark:text-emerald-500 font-bold hover:underline ml-6 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div class="bg-gray-50 dark:bg-warmdark-bg p-4 rounded-xl border border-gray-200 dark:border-warmdark-border transition-colors">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">Statistician Requirements</label>
                            <template x-for="(req, index) in requirements.req_desc_statistician" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 w-4 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" x-model="requirements.req_desc_statistician[index]" class="flex-1 border border-gray-300 dark:border-warmdark-border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500 transition-colors bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 placeholder-gray-400" placeholder="Enter requirement here...">
                                    <button type="button" @click="requirements.req_desc_statistician.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="requirements.req_desc_statistician.push('')" class="text-xs text-emerald-600 dark:text-emerald-500 font-bold hover:underline ml-6 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Requirement
                            </button>
                        </div>

                        <div x-show="reqMsg" class="mt-2 p-3 rounded-lg text-sm font-medium border" :class="isReqError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                            <span x-text="reqMsg"></span>
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="isReqLoading" class="bg-emerald-600 dark:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isReqLoading">Save Requirements</span>
                                <span x-show="isReqLoading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <div class="space-y-6">
            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-amber-50/50 dark:bg-yellow-900/10 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <h2 class="text-sm font-bold text-amber-900 dark:text-yellow-500 uppercase tracking-wider">Update Proposal Certificate</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateCertificate">
                        <div class="flex flex-col gap-6">
                            
                            <div class="w-full flex flex-col items-center">
                                <span class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 self-start">Current Proposal Certificate</span>
                                
                                <div class="w-full aspect-[1/1.4] bg-gray-100 dark:bg-warmdark-bg border-2 border-dashed border-gray-300 dark:border-warmdark-border rounded-lg overflow-hidden relative flex justify-center items-center">
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
                                        <span class="text-gray-400 dark:text-gray-600 text-xs font-medium">No File Found</span>
                                    </template>
                                </div>
                            </div>

                            <div class="w-full flex flex-col justify-center">
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Upload New Blank Certificate</label>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Upload a high-quality PNG, JPG, or PDF document.</p>
                                
                                <input type="file" id="certFile" accept="image/png, image/jpeg, image/jpg, application/pdf" @change="handleFileChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 dark:file:bg-yellow-900/30 file:text-amber-700 dark:file:text-yellow-500 hover:file:bg-amber-100 dark:hover:file:bg-yellow-900/50 transition-colors border border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-bg rounded-lg mb-4 cursor-pointer">

                                <div x-show="certMsg" class="mb-4 p-3 rounded-lg text-sm font-medium border" :class="isCertError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                                    <span x-text="certMsg"></span>
                                </div>

                                <div>
                                    <button type="submit" :disabled="!certFile || isCertLoading" class="w-full bg-amber-600 dark:bg-yellow-600 text-white font-semibold py-2.5 rounded-lg hover:bg-amber-700 dark:hover:bg-yellow-500 transition-colors disabled:opacity-50 text-sm shadow-sm flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                        <span x-show="!isCertLoading">Replace Template</span>
                                        <span x-show="isCertLoading" x-cloak>Uploading...</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Admin Credentials</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateProfile">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Admin Username / School ID</label>
                                <input type="text" x-model="profile.school_id" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Recovery Email</label>
                                <input type="email" x-model="profile.email" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div x-show="profileMsg" class="mt-4 p-3 rounded-lg text-sm font-medium border" :class="isProfileError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                            <span x-text="profileMsg"></span>
                        </div>

                        <div class="mt-5">
                            <button type="submit" :disabled="isProfileLoading" class="w-full bg-gray-900 dark:bg-gray-700 text-white font-semibold py-2.5 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isProfileLoading">Save Credentials</span>
                                <span x-show="isProfileLoading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Change Password</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updatePassword">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Current Password</label>
                                <input type="password" x-model="passwords.current" required class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">New Password</label>
                                <input type="password" x-model="passwords.new" required minlength="6" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Confirm New Password</label>
                                <input type="password" x-model="passwords.confirm" required minlength="6" class="w-full border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div x-show="passMsg" class="mt-4 p-3 rounded-lg text-sm font-medium border" :class="isPassError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                            <span x-text="passMsg"></span>
                        </div>

                        <div class="mt-6">
                            <button type="submit" :disabled="isPassLoading" class="w-full bg-blue-600 dark:bg-blue-700 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isPassLoading">Update Password</span>
                                <span x-show="isPassLoading" x-cloak>Updating...</span>
                            </button>
                        </div>
                    </form>
                </div>
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

        // Requirements Config (Now dynamically parsed from JSON array)
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

        // Password
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

        // NEW: Method to save array upload requirements as JSON string
        updateRequirements() {
            this.isReqLoading = true; this.reqMsg = '';
            
            // Stringify the arrays and remove empty rows before sending
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
                    this.passwords.current = ''; this.passwords.new = ''; this.passwords.confirm = '';
                    setTimeout(() => { this.passMsg = ''; }, 3000);
                }
            })
            .catch(err => {
                this.isPassLoading = false; this.isPassError = true; this.passMsg = "Connection error.";
            });
        }
    }
}
</script>
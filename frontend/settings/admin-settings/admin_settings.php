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

// 3. UNIVERSITY STANDARD SCHOOL YEAR GENERATION
$start_year = 2024; // The permanent year your system went live
$current_calendar_year = (int)date("Y"); 

// Generate up to 2 years into the future for advanced planning (e.g., preparing for next enrollment)
$max_year = $current_calendar_year + 2; 

$generated_years = [];
// Loop backwards so the newest/upcoming years are at the top of the dropdown
for ($y = $max_year; $y >= $start_year; $y--) {
    $next_y = $y + 1;
    $generated_years[] = $y . "-" . $next_y;
}
?>

<div class="max-w-5xl mx-auto pb-10" x-data="adminSettingsApp()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">System Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Manage global system configurations and your admin credentials.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-fit">
                <div class="p-5 border-b border-gray-100 bg-blue-50/50 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <h2 class="text-sm font-bold text-blue-900 uppercase tracking-wider">Global System Settings</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateSystem">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Active School Year</label>
                            <p class="text-xs text-gray-400 mb-3">This controls the default school year assigned to new students and filters the dashboards.</p>
                            <select x-model="system.school_year" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white font-medium text-gray-700">
                                <?php foreach($generated_years as $year): ?>
                                    <option value="<?php echo $year; ?>">SY <?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div x-show="sysMsg" class="mt-4 p-3 rounded-lg text-sm font-medium" :class="isSysError ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'" x-cloak>
                            <span x-text="sysMsg"></span>
                        </div>

                        <div class="mt-5">
                            <button type="submit" :disabled="isSysLoading" class="bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isSysLoading">Apply Global Setting</span>
                                <span x-show="isSysLoading" x-cloak>Applying...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-fit">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Admin Credentials</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="updateProfile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Admin Username / School ID</label>
                                <input type="text" x-model="profile.school_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Recovery Email</label>
                                <input type="email" x-model="profile.email" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors">
                            </div>
                        </div>

                        <div x-show="profileMsg" class="mt-4 p-3 rounded-lg text-sm font-medium" :class="isProfileError ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'" x-cloak>
                            <span x-text="profileMsg"></span>
                        </div>

                        <div class="mt-5">
                            <button type="submit" :disabled="isProfileLoading" class="bg-gray-900 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 text-sm shadow-sm">
                                <span x-show="!isProfileLoading">Save Credentials</span>
                                <span x-show="isProfileLoading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-fit">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Change Password</h2>
            </div>
            <div class="p-6">
                <form @submit.prevent="updatePassword">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Current Password</label>
                            <input type="password" x-model="passwords.current" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" x-model="passwords.new" required minlength="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" x-model="passwords.confirm" required minlength="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    <div x-show="passMsg" class="mt-4 p-3 rounded-lg text-sm font-medium" :class="isPassError ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'" x-cloak>
                        <span x-text="passMsg"></span>
                    </div>

                    <div class="mt-6">
                        <button type="submit" :disabled="isPassLoading" class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 text-sm shadow-sm">
                            <span x-show="!isPassLoading">Update Password</span>
                            <span x-show="isPassLoading" x-cloak>Updating...</span>
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
        system: { school_year: <?php echo json_encode($active_sy); ?> },
        isSysLoading: false, sysMsg: '', isSysError: false,

        profile: {
            school_id: <?php echo json_encode($admin['school_id'] ?? ''); ?>,
            email: <?php echo json_encode($admin['email'] ?? ''); ?>
        },
        isProfileLoading: false, profileMsg: '', isProfileError: false,

        passwords: { current: '', new: '', confirm: '' },
        isPassLoading: false, passMsg: '', isPassError: false,

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
                console.error(err);
                this.isSysLoading = false; this.isSysError = true; this.sysMsg = "Connection error.";
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
                console.error(err);
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
                console.error(err);
                this.isPassLoading = false; this.isPassError = true; this.passMsg = "Connection error.";
            });
        }
    }
}
</script>
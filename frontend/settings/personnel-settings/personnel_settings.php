<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];

// Fetch from users table (email) and personnel table
$stmt = $conn->prepare("
    SELECT u.email, p.full_name 
    FROM users u
    JOIN personnel p ON u.id = p.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$personnel = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="max-w-5xl mx-auto transition-colors duration-200" x-data="personnelSettingsApp()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Account Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your personnel profile and security credentials.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors lg:col-span-2">
            <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Appearance</h2>
            </div>
            <div class="p-6">
                <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Theme Preference</label>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">Choose how the dashboard looks. This setting is saved in your browser.</p>
                
                <select x-model="theme" @change="updateTheme" class="w-full md:w-1/2 border border-gray-300 dark:border-warmdark-border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white dark:bg-warmdark-bg font-medium text-gray-700 dark:text-gray-200">
                    <option value="light">Light Mode</option>
                    <option value="dark">Dark Mode (Warm)</option>
                </select>
            </div>
        </div>
        
        <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
            <div class="p-5 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg">
                <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Profile Information</h2>
            </div>
            <div class="p-6">
                <form @submit.prevent="updateProfile">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" x-model="profile.email" required
                                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" x-model="profile.full_name" required
                                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>
                    </div>

                    <div x-show="profileMsg" class="mt-4 p-3 rounded-lg text-sm font-medium border" 
                         :class="isProfileError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                        <span x-text="profileMsg"></span>
                    </div>

                    <div class="mt-6">
                        <button type="submit" :disabled="isProfileLoading" 
                                class="w-full bg-gray-900 dark:bg-gray-700 text-white font-semibold py-2.5 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">
                            <span x-show="!isProfileLoading">Save Profile Changes</span>
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
                            <input type="password" x-model="passwords.current" required
                                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" x-model="passwords.new" required minlength="6"
                                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" x-model="passwords.confirm" required minlength="6"
                                class="w-full border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>
                    </div>

                    <div x-show="passMsg" class="mt-4 p-3 rounded-lg text-sm font-medium border" 
                         :class="isPassError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'" x-cloak>
                        <span x-text="passMsg"></span>
                    </div>

                    <div class="mt-6">
                        <button type="submit" :disabled="isPassLoading" 
                                class="w-full bg-blue-600 dark:bg-blue-700 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50">
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
function personnelSettingsApp() {
    return {
        // Theme config
        userId: <?php echo json_encode($user_id); ?>,
        get themeKey() { return 'theme_user_' + this.userId; },
        theme: 'light',

        // Profile Data
        profile: {
            email: <?php echo json_encode($personnel['email'] ?? ''); ?>,
            full_name: <?php echo json_encode($personnel['full_name'] ?? ''); ?>
        },
        isProfileLoading: false,
        profileMsg: '',
        isProfileError: false,

        // Password Data
        passwords: { current: '', new: '', confirm: '' },
        isPassLoading: false,
        passMsg: '',
        isPassError: false,

        init() {
            this.theme = localStorage.getItem(this.themeKey) || 'light';
        },

        updateTheme() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem(this.themeKey, 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem(this.themeKey, 'light');
            }
        },

        updateProfile() {
            this.isProfileLoading = true;
            this.profileMsg = '';
            
            fetch('../../backend/ajax/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: 'personnel',
                    email: this.profile.email,
                    full_name: this.profile.full_name
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isProfileLoading = false;
                this.isProfileError = !data.success;
                this.profileMsg = data.message;
                if(data.success) {
                    setTimeout(() => { window.location.reload(); }, 1500); 
                }
            })
            .catch(() => {
                this.isProfileLoading = false;
                this.isProfileError = true;
                this.profileMsg = "Connection error. Please try again.";
            });
        },

        updatePassword() {
            this.passMsg = '';
            if (this.passwords.new !== this.passwords.confirm) {
                this.isPassError = true;
                this.passMsg = "New passwords do not match.";
                return;
            }

            this.isPassLoading = true;
            fetch('../../backend/ajax/update_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: this.passwords.current,
                    new_password: this.passwords.new
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isPassLoading = false;
                this.isPassError = !data.success;
                this.passMsg = data.message;
                if (data.success) {
                    this.passwords.current = ''; this.passwords.new = ''; this.passwords.confirm = '';
                    setTimeout(() => { window.location.reload(); }, 1500);
                }
            })
            .catch(() => {
                this.isPassLoading = false;
                this.isPassError = true;
                this.passMsg = "Connection error. Please try again.";
            });
        }
    }
}
</script>
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

// Extract initials for the modern avatar
$nameParts = explode(' ', trim($personnel['full_name'] ?? 'P N'));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
?>

<div class="max-w-5xl mx-auto py-6 px-4 transition-colors duration-200" x-data="personnelSettingsApp()">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Account Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your personnel profile and security credentials.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- MAIN PROFILE SECTION (Left Side, Takes 2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden transition-colors">
                
                <!-- Card Header with Avatar -->
                <div class="p-8 border-b border-gray-100 dark:border-warmdark-border flex flex-col sm:flex-row items-center sm:items-start gap-6 relative">
                    <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-warmdark-bg opacity-10"></div>
                    
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center text-white text-2xl font-bold shadow-lg ring-4 ring-white dark:ring-warmdark-panel z-10">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    
                    <div class="text-center sm:text-left z-10 pt-2">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100"><?= htmlspecialchars($personnel['full_name'] ?? 'Personnel') ?></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($personnel['email'] ?? 'No Email') ?></p>
                        <span class="inline-block mt-3 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wider rounded-full border border-blue-100 dark:border-blue-800/50">
                            Personnel Account
                        </span>
                    </div>
                </div>

                <!-- Form Body -->
                <div class="p-8">
                    <form @submit.prevent="updateProfile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Full Name -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Full Name</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input type="text" x-model="profile.full_name" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
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

                        <!-- Save Profile Button -->
                        <div class="mt-8 flex justify-end">
                            <button type="submit" :disabled="isProfileLoading" 
                                    class="bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg x-show="!isProfileLoading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                <span x-show="!isProfileLoading">Save Changes</span>
                                
                                <!-- Spinner -->
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
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Want to update your security credentials? Change your password here.</p>
                    </div>
                    <button type="button" @click="openPassModal" class="shrink-0 bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-warmdark-hover font-semibold py-2 px-5 rounded-lg text-sm shadow-sm transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Update Password
                    </button>
                </div>
            </div>
        </div>

        <!-- SIDEBAR SECTION (Right Side, Takes 1 column) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Appearance Card -->
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden h-fit transition-colors">
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
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100" id="modal-title">Update Password</h3>
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

                    <!-- Password Modal Alert -->
                    <div x-show="passMsg" x-collapse x-cloak class="mt-4">
                        <div class="p-3 rounded-lg text-sm font-medium border flex items-start gap-2" 
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

        // Password Data & Modal State
        isPassModalOpen: false,
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
                    this.passwords = { current: '', new: '', confirm: '' };
                    setTimeout(() => { this.closePassModal(); }, 1500);
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
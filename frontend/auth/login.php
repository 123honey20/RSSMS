<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RSSMS - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Check for dark mode preference
        if (localStorage.getItem('darkMode') === 'enabled' || 
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        warmdark: {
                            bg: '#0F172A', // Slate 900
                            panel: '#1E293B', // Slate 800
                            border: '#334155' // Slate 700
                        },
                        university: {
                            gold: '#D4AF37',
                            blue: '#1E3A8A'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-slate-50 dark:bg-warmdark-bg min-h-screen transition-colors duration-500">

    <button onclick="toggleDarkMode()" class="fixed top-8 right-8 p-3 bg-white dark:bg-warmdark-panel rounded-2xl shadow-xl border border-slate-200 dark:border-warmdark-border z-50 transition-all hover:scale-110 active:scale-95">
        <svg id="sunIcon" class="w-5 h-5 text-university-gold hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" /></svg>
        <svg id="moonIcon" class="w-5 h-5 text-slate-700 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
    </button>

    <div class="flex min-h-screen">
        
        <div class="hidden lg:flex lg:w-3/5 bg-university-blue relative overflow-hidden items-center justify-center p-12">
            <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
            
            <div class="relative z-10 text-center max-w-xl">
                <img src="../images/smcc logo.png" alt="Logo" class="w-24 h-24 mx-auto mb-8 drop-shadow-2xl">
                <h2 class="text-white text-3xl xl:text-3xl font-bold leading-tight tracking-tight mb-6">
                    Research Support Services <br> <span class="text-blue-300 font-medium text-2xl xl:text-3xl uppercase tracking-[0.3em]">Monitoring System</span>
                </h2>
                <div class="h-1.5 w-20 bg-university-gold mx-auto rounded-full mb-8"></div>
                <p class="text-blue-100 text-lg opacity-80 leading-relaxed font-light">
                    The centralized monitoring system for academic research excellence and support services at Saint Michael College of Caraga.
                </p>
            </div>
        </div>

        <div class="w-full lg:w-2/5 flex items-center justify-center p-6 sm:p-12 md:p-20">
            <div class="w-full max-w-md">
                
                <div class="flex lg:hidden items-center gap-4 mb-10">
                    <img src="../images/smcc logo.png" alt="Logo" class="w-12 h-12">
                    <h1 class="text-2xl font-bold dark:text-white text-slate-800">RSSMS</h1>
                </div>

                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 tracking-tight">Sign In</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">Please enter your credentials.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 rounded-2xl flex items-center gap-3 animate-pulse <?php echo $_GET['error'] === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        <span class="text-sm font-semibold">
                            <?php echo $_GET['error'] === 'Pending' ? "Account activation pending approval." : "Invalid ID or Password."; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <form action="../../backend/actions/login_action.php" method="POST" class="space-y-6">
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 ml-1">School ID</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                                <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <input type="text" name="school_id" required 
                                placeholder="Enter your School ID" 
                                class="w-full pl-12 pr-4 py-4 bg-white dark:bg-warmdark-panel border border-slate-200 dark:border-warmdark-border rounded-2xl text-sm focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 dark:text-white transition-all shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 ml-1">Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                                <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <input id="password" type="password" name="password" required 
                                placeholder="••••••••" 
                                class="w-full pl-12 pr-12 py-4 bg-white dark:bg-warmdark-panel border border-slate-200 dark:border-warmdark-border rounded-2xl text-sm focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 dark:text-white transition-all shadow-sm">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-4 rounded-2xl shadow-xl shadow-blue-500/20 transition transform active:scale-95 flex items-center justify-center gap-3">
                        <span>Authenticate</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>

                    <div class="text-center mt-8">
                        <p class="text-slate-500 dark:text-slate-400 font-medium">
                            First time here? 
                            <a href="register.php" class="text-blue-600 hover:text-blue-700 font-bold ml-1 transition-colors underline decoration-2 underline-offset-4">Create Account</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        }

        function togglePassword() {
            const passwordInput = document.getElementById("password");
            passwordInput.type = (passwordInput.type === "password") ? "text" : "password";
        }
    </script>
</body>
</html>
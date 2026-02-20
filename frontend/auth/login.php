<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RSSMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-200 flex items-center justify-center min-h-screen">

    <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-4xl flex">

        <div class="w-full md:w-1/2 p-8">

            <div class="flex items-center gap-3 mb-6">
                <img src="../images/smcc logo.png" alt="Logo" class="w-11 h-11 object-contain">
                <span class="font-semibold text-gray-700">Saint Michael College of Caraga</span>
            </div>

            <h1 class="text-lg font-bold mb-1">LOGIN</h1>
            <p class="text-xs text-gray-500 mb-6">Enter your credentials to access your account</p>

            <!-- ERROR MESSAGE -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-3 p-2 rounded text-white text-ms text-center overflow-hidden
                <?php echo $_GET['error'] === 'Pending' ? 'bg-yellow-500' : 'bg-red-500'; ?>">
                    <?php
                    if ($_GET['error'] === 'Pending') {
                        echo "Your account is still pending";
                    } else {
                        echo "Invalid School ID or Password.";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form action="../../backend/actions/login_action.php" method="POST" class="space-y-5">

                <div>
                    <label class="block text-xs font-medium mb-1">ID number</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5.121 17.804A4 4 0 019 15h6a4 4 0 013.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <input type="text" name="school_id" required
                            placeholder="Enter your ID number"
                            class="text-sm w-full bg-transparent py-3 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1">Password</label>
                    <div class="flex items-center bg-gray-100 rounded-lg px-3">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 11c1.657 0 3 1.343 3 3v3H9v-3c0-1.657 1.343-3 3-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 11V7a5 5 0 00-10 0v4" />
                        </svg>

                        <input id="password" type="password" name="password" required
                            placeholder="Enter your password"
                            class="text-sm w-full bg-transparent py-3 focus:outline-none">

                        <button type="button" onclick="togglePassword()" class="ml-2 text-gray-500 hover:text-gray-700">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>


                <div class="text-right">
                    <a href="#" class="text-xs text-gray-600 hover:underline">Forgot Password?</a>
                </div>

                <button type="submit"
                    class="w-full bg-blue-900 text-white text-sm py-2 rounded-lg hover:bg-blue-800 transition">
                    Sign in
                </button>

                <div class="flex items-center my-4">
                    <div class="flex-grow border-t"></div>
                    <span class="mx-3 text-sm text-gray-400">Or</span>
                    <div class="flex-grow border-t"></div>
                </div>

                <p class="text-center text-sm text-gray-600">
                    Don’t have an account?
                    <a href="register.php" class="text-blue-700 font-medium hover:underline">Sign Up</a>
                </p>

            </form>
        </div>

        <div class="hidden md:flex md:w-1/2 items-center justify-center bg-gradient-to-br from-blue-500 to-blue-900 p-10">
            <h2 class="text-white text-2xl font-semibold text-center leading-relaxed tracking-wide mb-40">
                RESEARCH SUPPORT <br>
                SERVICES AND MONITORING <br>
                SYSTEM
            </h2>
        </div>
        

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eyeIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }

        if (window.location.search.includes("error=")) {
            const url = new URL(window.location);
            url.searchParams.delete("error");
            window.history.replaceState({}, document.title, url.pathname);
        }
    </script>


</body>

</html>
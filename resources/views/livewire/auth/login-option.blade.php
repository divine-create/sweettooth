<div>
     <div class="w-full max-w-4xl">
   <!-- Cards Container -->
        <div class="flex flex-col md:flex-row gap-8 justify-center items-center">
            <!-- Admin Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden w-full max-w-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-2 border border-zinc-200 dark:border-zinc-700">
                <div class="p-6">
                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-2xl"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-center text-zinc-800 dark:text-zinc-100 mb-2">Admin Login</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 text-center mb-6">Access administrative functions and system settings</p>
                    <ul class="text-sm text-zinc-600 dark:text-zinc-400 mb-6 space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Full system access
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            User management
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Analytics & reports
                        </li>
                    </ul>
                    <a class="block flex justify-center w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg font-medium transition-all duration-300 hover:from-blue-600 hover:to-indigo-700 shadow-md hover:shadow-lg"
                    href="{{ route('login') }}" wire:navigate
                    >
                        Login as Admin
                    </a>
                </div>
            </div>

            <!-- Staff Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden w-full max-w-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-2 border border-zinc-200 dark:border-zinc-700">
                <div class="p-6">
                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 flex items-center justify-center">
                            <i class="fas fa-users text-white text-2xl"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-center text-zinc-800 dark:text-zinc-100 mb-2">Staff Login</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 text-center mb-6">Access staff dashboard and daily operations</p>
                    <ul class="text-sm text-zinc-600 dark:text-zinc-400 mb-6 space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Task management
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Client interactions
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Daily reporting
                        </li>
                    </ul>
                    <a wire:navigate href="{{ route('staff-login') }}"
                    class="block flex justify-center w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg font-medium transition-all duration-300 hover:from-blue-600 hover:to-indigo-700 shadow-md hover:shadow-lg"
                    >
                        Login as Staff
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

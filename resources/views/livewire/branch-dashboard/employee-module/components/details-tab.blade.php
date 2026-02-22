<nav class="flex overflow-x-auto">
    <button @click="activeTab = 'personal'"
        :class="activeTab === 'personal' ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="py-4 px-6 border-b-2 font-medium whitespace-nowrap transition-all">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Personal Info
        </div>
    </button>
    <button @click="activeTab = 'employment'"
        :class="activeTab === 'employment' ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="py-4 px-6 border-b-2 font-medium whitespace-nowrap transition-all">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Employment
        </div>
    </button>
    <button @click="activeTab = 'financial'"
        :class="activeTab === 'financial' ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="py-4 px-6 border-b-2 font-medium whitespace-nowrap transition-all">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Financial
        </div>
    </button>
    <button @click="activeTab = 'leave'"
        :class="activeTab === 'leave' ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="py-4 px-6 border-b-2 font-medium whitespace-nowrap transition-all">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Leave Information
            @if($leaveStats['pending_applications'] > 0)
                <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-500 text-white">{{ $leaveStats['pending_applications'] }}</span>
            @endif
        </div>
    </button>
    <button @click="activeTab = 'emergency'"
        :class="activeTab === 'emergency' ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-zinc-800' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="py-4 px-6 border-b-2 font-medium whitespace-nowrap transition-all">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            Emergency
        </div>
    </button>
</nav>
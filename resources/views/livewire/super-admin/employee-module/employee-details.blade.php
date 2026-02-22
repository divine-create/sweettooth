<div>
    <div x-data="{
        activeTab: 'personal',
        darkMode: false,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
        }
    }" class="container mx-auto px-4 py-2 max-w-6xl">
        <x-breadcrumb title="Employee Details" :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Employees', 'url' => route('super-admin.employee.index')],
            ['label' => 'Employee Details'],
        ]" :compact="true" :with-icons="true" />

        <!-- Main Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden transition-colors duration-300 mt-6">
            <!-- Employee Details Section -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Profile Photo -->
                    <div class="relative">
                        <div
                            class="w-32 h-32 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center overflow-hidden">
                            <i class="fas fa-user text-5xl text-gray-500 dark:text-gray-400"></i>
                        </div>
                        <div
                            class="absolute bottom-0 right-0 bg-green-500 w-6 h-6 rounded-full border-2 border-white dark:border-gray-800">
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $employee->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            @foreach ($employee->getRoleNames() as $role)
                                <span
                                    class="px-3 py-1 rounded-sm text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">{{ $role }}</span>
                            @endforeach
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2"> {{ $employee->employee_number }} </p>

                        <div class="flex flex-wrap gap-2 mt-4 justify-center md:justify-start">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Engineering</span>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Morning</span>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg w-full md:w-64">
                        <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Employment Details</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Hire Date:</span>
                                <span
                                    class="text-gray-800 dark:text-white text-sm font-medium">{{ \Carbon\Carbon::parse($employee->hire_date)->format('d. M, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Probation End:</span>
                                <span class="text-gray-800 dark:text-white text-sm font-medium">2022-08-15</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Last Review:</span>
                                <span class="text-gray-800 dark:text-white text-sm font-medium">2023-04-20</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex overflow-x-auto">
                    <button @click="activeTab = 'personal'"
                        :class="activeTab === 'personal'
                            ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="tab-button py-4 px-6 border-b-2 font-medium whitespace-nowrap">
                        Personal Info
                    </button>
                    <button @click="activeTab = 'employment'"
                        :class="activeTab === 'employment'
                            ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="tab-button py-4 px-6 border-b-2 font-medium whitespace-nowrap">
                        Employment
                    </button>
                    <button @click="activeTab = 'financial'"
                        :class="activeTab === 'financial'
                            ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="tab-button py-4 px-6 border-b-2 font-medium whitespace-nowrap">
                        Financial
                    </button>
                    <button @click="activeTab = 'emergency'"
                        :class="activeTab === 'emergency'
                            ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="tab-button py-4 px-6 border-b-2 font-medium whitespace-nowrap">
                        Emergency Contact
                    </button>
                    <button @click="activeTab = 'performance'"
                        :class="activeTab === 'performance'
                            ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="tab-button py-4 px-6 border-b-2 font-medium whitespace-nowrap">
                        Performance
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Personal Info Tab -->
                <div x-show="activeTab === 'personal'" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->email ?? '—' }}</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->phone ?? '—' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of
                                    Birth</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('d. M, Y') : '—' }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender</label>
                                <p class="text-gray-900 dark:text-white">{{ ucfirst($employee->gender ?? '—') }}</p>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->address ?? '—' }}</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nationality</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->nationality ?? '—' }}</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allergies</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->allergies ?? 'None' }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Employment Tab -->
                <div x-show="activeTab === 'employment'" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Branch</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->branch->name ?? '—' }}</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->department->name ?? '—' }}</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->position ?? '—' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hire
                                    Date</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('d. M, Y') : '—' }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <p class="text-gray-900 dark:text-white">{{ ucfirst($employee->status ?? '—') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shift
                                    Preference</label>
                                <p class="text-gray-900 dark:text-white">{{ $employee->shift_preference ?? '—' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Probation
                                    End Date</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ $employee->probation_end_date ? \Carbon\Carbon::parse($employee->probation_end_date)->format('d. M, Y') : '—' }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Termination
                                    Date</label>
                                <p class="text-gray-900 dark:text-white">
                                    {{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('d. M, Y') : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Financial Tab -->
<!-- Financial Tab -->
<div x-show="activeTab === 'financial'" class="tab-content">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Salary</label>
                <p class="text-gray-900 dark:text-white">
                    {{ $employee->salary ? \App\Helpers\LocalizationHelper::formatCurrency($employee->salary) : '—' }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hourly Rate</label>
                <p class="text-gray-900 dark:text-white">
                    {{ $employee->hourly_rate ? \App\Helpers\LocalizationHelper::formatCurrency($employee->hourly_rate) : '—' }}
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tax ID</label>
                <p class="text-gray-900 dark:text-white">{{ $employee->tax_id ?? '—' }}</p>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Account</label>
                <p class="text-gray-900 dark:text-white">
                    {{ $employee->bank_account ? $employee->bank_account : '—' }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contact Tab -->
<div x-show="activeTab === 'emergency'" class="tab-content">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Emergency Contact Name
            </label>
            <p class="text-gray-900 dark:text-white">{{ $employee->emergency_contact_name ?? '—' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Emergency Contact Phone
            </label>
            <p class="text-gray-900 dark:text-white">{{ $employee->emergency_contact_phone ?? '—' }}</p>
        </div>
    </div>
</div>

<!-- Performance Tab -->
<div x-show="activeTab === 'performance'" class="tab-content">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Last Performance Review Date
            </label>
            <p class="text-gray-900 dark:text-white">
                {{ $employee->last_review_date ? \Carbon\Carbon::parse($employee->last_review_date)->format('d. M, Y') : '—' }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Performance Rating
            </label>
            <div class="flex items-center">
                @php
                    $rating = $employee->performance_rating ?? 0;
                    $percent = min(100, ($rating / 5) * 100);
                @endphp
                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-2">
                    <div class="bg-yellow-500 h-2.5 rounded-full" style="width: {{ $percent }}%"></div>
                </div>
                <span class="text-gray-900 dark:text-white font-medium">
                    {{ number_format($rating, 1) }} / 5.0
                </span>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>

</div>

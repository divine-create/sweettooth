<div>
    <div x-data="{
        activeTab: 'personal',
    }" class="p-3 space-y-3">
        <x-breadcrumb title="Employee Details" :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Employees', 'url' =>''],
            ['label' => 'Employee Details'],
        ]" :compact="false" :with-icons="true" />

        <!-- Main Content -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden">
            <!-- Employee Header Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Profile Photo -->
                    <div class="relative">
                        <div class="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center overflow-hidden border-4 border-white/30">
                            @if($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-20 h-20 text-white/80" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                        <div class="absolute bottom-2 right-2 bg-green-500 w-5 h-5 rounded-full border-2 border-white"></div>
                    </div>

                    <!-- Basic Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-3xl font-bold">{{ $employee->name }}</h2>
                        <p class="text-blue-100 text-lg mt-1">{{ $employee->position ?? 'Employee' }}</p>
                        <p class="text-blue-200 text-sm mt-1">{{ $employee->employee_number }}</p>

                        <div class="flex flex-wrap gap-2 mt-4 justify-center md:justify-start">
                            @foreach ($employee->getRoleNames() as $role)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-sm border border-white/30">
                                    {{ $role }}
                                </span>
                            @endforeach
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $employee->status === 'active' ? 'bg-green-500/80' : 'bg-red-500/80' }} border border-white/30">
                                {{ ucfirst($employee->status ?? 'active') }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 p-4 rounded-lg w-full md:w-72">
                        <h3 class="font-semibold mb-3 text-sm uppercase tracking-wide">Quick Info</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-blue-100">Employee ID:</span>
                                <span class="font-semibold">{{ $employee->employee_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-blue-100">Department:</span>
                                <span class="font-semibold">{{ $employee->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-blue-100">Branch:</span>
                                <span class="font-semibold">{{ $employee->branch->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-blue-100">Hire Date:</span>
                                <span class="font-semibold text-xs">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
              @includeIf('livewire.branch-dashboard.employee-module.components.details-tab')
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Personal Info Tab -->
                @includeIf('livewire.branch-dashboard.employee-module.components.tabs.personal')

                <!-- Employment Tab -->
                @includeIf('livewire.branch-dashboard.employee-module.components.tabs.employment')

                <!-- Financial Tab -->
                @includeIf('livewire.branch-dashboard.employee-module.components.tabs.finance')

                <!-- Leave Information Tab -->
                @includeIf('livewire.branch-dashboard.employee-module.components.tabs.leave')


                <!-- Emergency Contact Tab -->
                <div x-show="activeTab === 'emergency'" class="tab-content">
                    <div class="space-y-4 max-w-2xl">
                        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Emergency Contact Name</label>
                            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->emergency_contact_name ?? '—' }}</p>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg">
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Emergency Contact Phone</label>
                            <p class="text-zinc-900 dark:text-white font-medium">{{ $employee->emergency_contact_phone ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

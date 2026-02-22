<div class="p-3 space-y-3">
    <x-breadcrumb title="Apply for Leave" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Leave Management'],
        ['label' => 'Apply Leave'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Apply for Leave</h2>
                <p class="text-sm opacity-90 mt-1">
                    Submit your leave application with all required details
                </p>
            </div>
            <a href="{{ branch_route('leave.my-leaves') }}"
                class="px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                My Leaves
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <!-- Leave Application Form -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Leave Application Form</h3>

                <div>
                    <div x-data="{ showSuccess: false, successMessage: '' }"
                         x-init="
                            $wire.on('leave-application-success', (event) => {
                                successMessage = event.message;
                                showSuccess = true;
                                setTimeout(() => { showSuccess = false; }, 5000);
                            });
                         "
                         x-show="showSuccess"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform translate-y-2"
                         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200"
                         style="display: none;">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span x-text="successMessage" class="text-sm"></span>
                        </div>
                    </div>
                </div>
                <form wire:submit.prevent="submit" class="space-y-4">
                    <!-- Leave Type -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Leave Type *
                        </label>
                        @if($leaveTypes->isEmpty())
                            <div class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 italic">
                                No leave types available. Please contact your administrator to set up leave types.
                            </div>
                        @else
                            <select wire:model.live="leave_type_id" required
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Leave Type</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }} ({{ $type->is_paid ? 'Paid' : 'Unpaid' }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('leave_type_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Leave Type Info -->
                    @if($selected_leave_type)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    <p class="font-medium">{{ $selected_leave_type->name }}</p>
                                    @if($selected_leave_type->description)
                                        <p class="text-xs mt-1">{{ $selected_leave_type->description }}</p>
                                    @endif
                                    <ul class="mt-2 space-y-1 text-xs">
                                        @if($selected_leave_type->min_notice_days > 0)
                                            <li>• Minimum notice: {{ $selected_leave_type->min_notice_days }} days</li>
                                        @endif
                                        @if($selected_leave_type->max_consecutive_days)
                                            <li>• Max consecutive days: {{ $selected_leave_type->max_consecutive_days }}</li>
                                        @endif
                                        @if($selected_leave_type->requires_document)
                                            <li>• Supporting document required</li>
                                        @endif
                                        <li>• Available balance: <span class="font-semibold">{{ number_format($available_balance, 1) }} days</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Start Date *
                            </label>
                            <input type="date" wire:model.live="start_date" required
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('start_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                End Date *
                            </label>
                            <input type="date" wire:model.live="end_date" required
                                min="{{ $start_date ?: now()->format('Y-m-d') }}"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('end_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Total Days Display -->
                    @if($total_days > 0)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                    Total Working Days: <span class="text-lg font-bold">{{ $total_days }}</span> days (weekends excluded)
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Reason for Leave *
                        </label>
                        <textarea wire:model="reason" rows="4" required
                            placeholder="Please provide a detailed reason for your leave application..."
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"></textarea>
                        @error('reason') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Emergency Contact -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Emergency Contact (Optional)
                        </label>
                        <input type="text" wire:model="emergency_contact"
                            placeholder="Name and phone number"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Supporting Document -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Supporting Document @if($selected_leave_type && $selected_leave_type->requires_document) * @endif
                        </label>
                        <input type="file" wire:model="supporting_document" accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Accepted formats: PDF, JPG, PNG (Max 2MB)
                        </p>
                        @error('supporting_document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <a href="{{ branch_route('leave.my-leaves') }}"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Balance Summary -->
        <div class="space-y-3">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Your Leave Balance</h3>

                <div class="space-y-3">
                    @forelse($leaveBalances as $balance)
                        <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: {{ $balance->leaveType->color }}"></div>
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $balance->leaveType->name }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">Total</p>
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($balance->total_days, 1) }}</p>
                                </div>
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">Used</p>
                                    <p class="font-semibold text-red-600 dark:text-red-400">{{ number_format($balance->used_days, 1) }}</p>
                                </div>
                                <div>
                                    <p class="text-zinc-500 dark:text-zinc-400">Remaining</p>
                                    <p class="font-semibold text-green-600 dark:text-green-400">{{ number_format($balance->remaining_days, 1) }}</p>
                                </div>
                            </div>
                            @if($balance->pending_days > 0)
                                <div class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                                    Pending: {{ number_format($balance->pending_days, 1) }} days
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-4">
                            No leave balances available
                        </p>
                    @endforelse
                </div>

                <a href="{{ branch_route('leave.balance') }}"
                    class="mt-4 block w-full text-center px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg text-sm font-medium transition-colors">
                    View Detailed Balance
                </a>
            </div>

            <!-- Quick Guide -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-indigo-900 dark:text-indigo-100 mb-2">Quick Guide</h4>
                <ul class="text-xs text-indigo-800 dark:text-indigo-200 space-y-1">
                    <li>• Check leave type requirements before applying</li>
                    <li>• Ensure sufficient leave balance</li>
                    <li>• Provide detailed reasons</li>
                    <li>• Upload documents if required</li>
                    <li>• Plan ahead for approval time</li>
                </ul>
            </div>
        </div>
    </div>
</div>

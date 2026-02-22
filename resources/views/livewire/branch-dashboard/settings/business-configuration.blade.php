@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="w-full">
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Business Configuration</h2>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg transition-opacity duration-300">
            <div class="flex justify-between items-center">
                <span>{{ session('message') }}</span>
                <button @click="show = false" class="text-green-700 dark:text-green-100">&times;</button>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-100 rounded-lg transition-opacity duration-300">
            <div class="flex justify-between items-center">
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-700 dark:text-red-100">&times;</button>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Company Information -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Company Information</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Company Name</label>
                    <input type="text" wire:model="companyName"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    @error('companyName')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Branding -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Branding</h3>
                <div class="flex items-start space-x-6">
                    <div class="flex-shrink-0">
                        @if($existingLogo && Storage::disk('public')->exists($existingLogo))
                            <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                <img src="{{ Storage::disk('public')->url($existingLogo) }}"
                                     alt="Current Logo"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full items-center justify-center bg-zinc-200 dark:bg-zinc-600 hidden" style="display: none;">
                                    <svg class="w-8 h-8 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                        @elseif($logo)
                            <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                <img src="{{ $logo->temporaryUrl() }}"
                                     alt="Logo Preview"
                                     class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="w-24 h-24 bg-zinc-200 dark:bg-zinc-600 rounded-lg flex items-center justify-center border-2 border-dashed border-zinc-400 dark:border-zinc-500">
                                <svg class="w-8 h-8 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" wire:model="logo" accept="image/*" class="hidden" id="logoUpload">
                        <label for="logoUpload"
                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-medium rounded-lg cursor-pointer transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            {{ $existingLogo ? 'Change Logo' : 'Upload Logo' }}
                        </label>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">
                            Upload logo for POS receipts, reports, and online shop
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                            Supported formats: JPEG, PNG, GIF. Max size: 2MB
                        </p>
                        @error('logo')
                            <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                        @if($logo)
                            <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                New logo selected: {{ $logo->getClientOriginalName() }}
                            </div>
                        @endif
                        @if($existingLogo)
                            <button type="button" wire:click="removeLogo"
                                class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 underline">
                                Remove current logo
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Details -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Contact Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Phone</label>
                        <input type="text" wire:model="phone"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
                        <input type="email" wire:model="email"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

              <!-- Backup Settings -->
              <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Backup Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Auto Backup</label>
                        <input type="checkbox" name="auto_backup" wire:model="auto_backup">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Backup Interval</label>
                        <input type="number" wire:model="backup_interval" min="2"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        @error('backup_interval')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Backup Period</label>
                    <select wire:model="backup_period"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="">Select Period</option>
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                </select>
                </div>
            </div>


            <!-- Save Button -->
            <div class="flex justify-end space-x-4">
                <button type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    Save Changes
                </button>
                <button type="button" wire:click="cancel"
                    class="bg-zinc-500 hover:bg-zinc-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>

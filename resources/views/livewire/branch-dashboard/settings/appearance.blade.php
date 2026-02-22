@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="w-full" x-data
     x-on:appearance-updated.window="
        if ($event.detail?.primaryColor) {
            document.documentElement.style.setProperty('--color-primary', $event.detail.primaryColor);
        }
        if ($event.detail?.accentColor) {
            document.documentElement.style.setProperty('--color-accent', $event.detail.accentColor);
            document.documentElement.style.setProperty('--color-accent-content', $event.detail.accentColor);
        }
        if ($event.detail?.primaryMuted) {
            document.documentElement.style.setProperty('--color-primary-muted', $event.detail.primaryMuted);
        }
        if ($event.detail?.pageBackground) {
            document.documentElement.style.setProperty('--color-background', $event.detail.pageBackground);
        }
        if ($event.detail?.primaryContrast) {
            document.documentElement.style.setProperty('--color-primary-foreground', $event.detail.primaryContrast);
        }
        if (window.Flux && typeof window.Flux.applyAppearance === 'function' && $event.detail?.themeMode) {
            window.Flux.applyAppearance($event.detail.themeMode);
        }
     ">
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Appearance & Theme</h2>

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

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-100 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Theme Mode -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Theme Mode</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <input type="radio" id="theme-system" wire:model="themeMode" value="system" 
                               class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <label for="theme-system" class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">System Default</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="theme-light" wire:model="themeMode" value="light" 
                               class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <label for="theme-light" class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Light Mode</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="theme-dark" wire:model="themeMode" value="dark" 
                               class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <label for="theme-dark" class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Dark Mode</label>
                    </div>
                </div>
            </div>

            <!-- Color Settings -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Color Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Primary Color</label>
                        <div class="flex items-center">
                            <input type="color" wire:model="primaryColor" 
                                   class="w-12 h-10 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800">
                            <input type="text" wire:model="primaryColor" 
                                   class="ml-2 w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Settings -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Layout Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sidebar Position</label>
                        <select wire:model="sidebarPosition" 
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">UI Density</label>
                        <select wire:model="uiDensity" 
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                            <option value="compact">Compact</option>
                            <option value="normal">Normal</option>
                            <option value="spacious">Spacious</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Font Settings -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Font Settings</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Font Size</label>
                    <select wire:model="fontSize" 
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="small">Small</option>
                        <option value="normal">Normal</option>
                        <option value="large">Large</option>
                    </select>
                </div>
            </div>

            <!-- Animation Settings -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Animation Settings</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="animationEnabled" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <label class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable Animations</label>
                </div>
            </div>

            <!-- Logo Settings -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Custom Logos</h3>
                
                <!-- Light Mode Logo -->
                <div class="mb-6">
                    <h4 class="text-md font-medium mb-2 text-zinc-800 dark:text-zinc-200">Light Mode Logo</h4>
                    <div class="flex items-start space-x-6">
                        <div class="flex-shrink-0">
                            @if($existingLightModeLogo && Storage::disk('public')->exists($existingLightModeLogo))
                                <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                    <img src="{{ Storage::disk('public')->url($existingLightModeLogo) }}"
                                         alt="Light Mode Logo"
                                         class="w-full h-full object-cover"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full items-center justify-center bg-zinc-200 dark:bg-zinc-600 hidden" style="display: none;">
                                        <svg class="w-8 h-8 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            @elseif($lightModeLogo)
                                <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                    <img src="{{ $lightModeLogo->temporaryUrl() }}"
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
                            <input type="file" wire:model="lightModeLogo" accept="image/*" class="hidden" id="lightModeLogoUpload">
                            <label for="lightModeLogoUpload"
                                class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-medium rounded-lg cursor-pointer transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $existingLightModeLogo ? 'Change Light Mode Logo' : 'Upload Light Mode Logo' }}
                            </label>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">
                                Upload logo for light mode theme
                            </p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                Supported formats: JPEG, PNG, GIF. Max size: 2MB
                            </p>
                            @error('lightModeLogo')
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                            @if($lightModeLogo)
                                <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    New logo selected: {{ $lightModeLogo->getClientOriginalName() }}
                                </div>
                            @endif
                            @if($existingLightModeLogo)
                                <button type="button" wire:click="removeLightModeLogo"
                                    class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 underline">
                                    Remove current light mode logo
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Dark Mode Logo -->
                <div>
                    <h4 class="text-md font-medium mb-2 text-zinc-800 dark:text-zinc-200">Dark Mode Logo</h4>
                    <div class="flex items-start space-x-6">
                        <div class="flex-shrink-0">
                            @if($existingDarkModeLogo && Storage::disk('public')->exists($existingDarkModeLogo))
                                <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                    <img src="{{ Storage::disk('public')->url($existingDarkModeLogo) }}"
                                         alt="Dark Mode Logo"
                                         class="w-full h-full object-cover"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full items-center justify-center bg-zinc-200 dark:bg-zinc-600 hidden" style="display: none;">
                                        <svg class="w-8 h-8 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            @elseif($darkModeLogo)
                                <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-600 rounded-lg overflow-hidden border-2 border-zinc-300 dark:border-zinc-500">
                                    <img src="{{ $darkModeLogo->temporaryUrl() }}"
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
                            <input type="file" wire:model="darkModeLogo" accept="image/*" class="hidden" id="darkModeLogoUpload">
                            <label for="darkModeLogoUpload"
                                class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-medium rounded-lg cursor-pointer transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $existingDarkModeLogo ? 'Change Dark Mode Logo' : 'Upload Dark Mode Logo' }}
                            </label>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">
                                Upload logo for dark mode theme
                            </p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                Supported formats: JPEG, PNG, GIF. Max size: 2MB
                            </p>
                            @error('darkModeLogo')
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                            @if($darkModeLogo)
                                <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    New logo selected: {{ $darkModeLogo->getClientOriginalName() }}
                                </div>
                            @endif
                            @if($existingDarkModeLogo)
                                <button type="button" wire:click="removeDarkModeLogo"
                                    class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 underline">
                                    Remove current dark mode logo
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end space-x-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
                <button type="button" wire:click="cancel" wire:loading.attr="disabled" wire:target="save,cancel"
                    class="bg-zinc-500 hover:bg-zinc-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>

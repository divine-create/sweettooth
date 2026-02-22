@if($showDepartmentModal)
    <x-modal wire:model="showDepartmentModal" title="Select Department">
        <div class="space-y-3">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                Please choose a department to generate this report.
            </p>
            <x-select.native label="Department" wire:model="selectedDepartmentId">
                <option value="">Select Department</option>
                @foreach($availableDepartments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </x-select.native>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-button color="secondary" wire:click="$set('showDepartmentModal', false)">
                    Cancel
                </x-button>
                <x-button color="primary" wire:click="confirmDepartmentSelection">
                    Continue
                </x-button>
            </div>
        </x-slot>
    </x-modal>
@endif

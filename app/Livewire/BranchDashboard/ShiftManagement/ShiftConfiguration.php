<?php

namespace App\Livewire\BranchDashboard\ShiftManagement;

use App\Livewire\BaseComponent;
use App\Models\Branch;
use App\Models\ShiftConfiguration as ShiftConfigurationModel;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ShiftConfiguration extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $configs;
    public $editingConfig = null;
    
    // Form fields
    public $name;
    public $shift_type;
    public $start_time;
    public $end_time;
    public $clock_in_start;
    public $clock_in_end;
    public $auto_clock_out_minutes = 15;
    public $max_overtime_hours = 2.00;
    public $break_duration_minutes = 60;
    public $timezone = 'UTC';
    public $is_active = true;

    public $showCreateForm = false;
    public $showEditForm = false;

    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public function mount()
    {
        $this->b_id = current_branch_id();
        $this->loadConfigs();
    }

    protected function getModelClass(): string
    {
        return ShiftConfigurationModel::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    public function loadConfigs()
    {
        $query = ShiftConfigurationModel::query()
            ->where('branch_id', $this->b_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('shift_type', 'like', '%' . $this->search . '%');
            });
        }

        $this->configs = $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadConfigs();
    }

    public function create()
    {
        $this->resetForm();
        $this->showCreateForm = true;
        $this->showEditForm = false;
    }

    public function store()
    {
        $this->prepareTimesForValidation();
        $this->validate([
            'name' => 'required|string|max:255',
            'shift_type' => 'required|string|in:morning,afternoon,night,full_time|max:50|unique:shift_configurations,shift_type,NULL,id,branch_id,' . $this->b_id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'clock_in_start' => 'required|date_format:H:i',
            'clock_in_end' => 'required|date_format:H:i|after:clock_in_start',
            'auto_clock_out_minutes' => 'required|integer|min:0',
            'max_overtime_hours' => 'required|numeric|min:0',
            'break_duration_minutes' => 'required|integer|min:0',
            'timezone' => 'required|string|max:50',
        ]);

        ShiftConfigurationModel::create([
            'branch_id' => $this->b_id,
            'name' => $this->name,
            'shift_type' => $this->shift_type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'clock_in_start' => $this->clock_in_start,
            'clock_in_end' => $this->clock_in_end,
            'auto_clock_out_minutes' => $this->auto_clock_out_minutes,
            'max_overtime_hours' => $this->max_overtime_hours,
            'break_duration_minutes' => $this->break_duration_minutes,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
        ]);

        $this->toast()->success('Shift configuration created successfully!')->send();
        $this->resetForm();
        $this->showCreateForm = false;
        $this->loadConfigs();
    }

    public function edit($id)
    {
        $config = ShiftConfigurationModel::findOrFail($id);
        
        $this->editingConfig = $config->id;
        $this->name = $config->name;
        $this->shift_type = $config->shift_type;
        $this->start_time = $this->formatTimeForDisplay($config->start_time);
        $this->end_time = $this->formatTimeForDisplay($config->end_time);
        $this->clock_in_start = $this->formatTimeForDisplay($config->clock_in_start);
        $this->clock_in_end = $this->formatTimeForDisplay($config->clock_in_end);
        $this->auto_clock_out_minutes = $config->auto_clock_out_minutes;
        $this->max_overtime_hours = $config->max_overtime_hours;
        $this->break_duration_minutes = $config->break_duration_minutes;
        $this->timezone = $config->timezone;
        $this->is_active = $config->is_active;

        $this->showEditForm = true;
        $this->showCreateForm = false;
    }

    public function update()
    {
        $this->prepareTimesForValidation();
        $this->validate([
            'name' => 'required|string|max:255',
            'shift_type' => 'required|string|in:morning,afternoon,night,full_time|max:50|unique:shift_configurations,shift_type,' . $this->editingConfig . ',id,branch_id,' . $this->b_id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'clock_in_start' => 'required|date_format:H:i',
            'clock_in_end' => 'required|date_format:H:i|after:clock_in_start',
            'auto_clock_out_minutes' => 'required|integer|min:0',
            'max_overtime_hours' => 'required|numeric|min:0',
            'break_duration_minutes' => 'required|integer|min:0',
            'timezone' => 'required|string|max:50',
        ]);

        $config = ShiftConfigurationModel::findOrFail($this->editingConfig);
        $config->update([
            'name' => $this->name,
            'shift_type' => $this->shift_type,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'clock_in_start' => $this->clock_in_start,
            'clock_in_end' => $this->clock_in_end,
            'auto_clock_out_minutes' => $this->auto_clock_out_minutes,
            'max_overtime_hours' => $this->max_overtime_hours,
            'break_duration_minutes' => $this->break_duration_minutes,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
        ]);

        $this->toast()->success('Shift configuration updated successfully!')->send();
        $this->resetForm();
        $this->showEditForm = false;
        $this->loadConfigs();
    }

    public function destroy($id)
    {
        $this->authorize('delete', ShiftConfigurationModel::findOrFail($id));

        ShiftConfigurationModel::destroy($id);
        $this->toast()->success('Shift configuration deleted successfully!')->send();
        $this->loadConfigs();
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showCreateForm = false;
        $this->showEditForm = false;
    }

    private function resetForm()
    {
        $this->name = '';
        $this->shift_type = '';
        $this->start_time = '6:00 AM';
        $this->end_time = '2:00 PM';
        $this->clock_in_start = '6:00 AM';
        $this->clock_in_end = '12:00 PM';
        $this->auto_clock_out_minutes = 15;
        $this->max_overtime_hours = 2.00;
        $this->break_duration_minutes = 60;
        $this->timezone = 'UTC';
        $this->is_active = true;
        $this->editingConfig = null;
    }

    private function prepareTimesForValidation(): void
    {
        $this->start_time = $this->normalizeTimeInput($this->start_time);
        $this->end_time = $this->normalizeTimeInput($this->end_time);
        $this->clock_in_start = $this->normalizeTimeInput($this->clock_in_start);
        $this->clock_in_end = $this->normalizeTimeInput($this->clock_in_end);
    }

    private function normalizeTimeInput($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\\d{1,2}:\\d{2}$/', $value)) {
            return $value;
        }

        $upper = strtoupper($value);
        $formats = ['g:i A', 'g:iA', 'h:i A', 'h:iA'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $upper)->format('H:i');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $value;
    }

    private function formatTimeForDisplay($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', $value)->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public function render()
    {
        $branch = current_branch();
        $this->loadConfigs();

        return view('livewire.branch-dashboard.shift-management.shift-configuration', [
            'configs' => $this->configs,
            'branch' => $branch,
        ]);
    }
}

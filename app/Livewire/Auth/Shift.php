<?php

namespace App\Livewire\Auth;

use App\Models\Branch;
use App\Models\SalesShift;
use App\Models\Shift as ShiftModel;
use App\Models\ShiftConfiguration;
use App\Services\CheckExpiredProducts;
use App\Services\ShiftTimingValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.auth')]
class Shift extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public $shift_type;

    public $shift_config_id;

    public $notes;

    // Current shift status
    public $currentShift = null;

    public $hasActiveShift = false;

    public function mount()
    {
        $this->b_id = request()->query('b_id') ?? session('selected_branch_id');
        if (! $this->b_id) {
            // Fallback to first active branch
            $defaultBranch = \App\Models\Branch::where('is_active', 1)->first();
            if ($defaultBranch) {
                $this->b_id = $defaultBranch->id;
                session(['selected_branch_id' => $this->b_id]);
            }
        } else {
            // Keep session in sync with explicit URL branch selection.
            session(['selected_branch_id' => $this->b_id]);
        }
        $this->loadCurrentShift();
    }

    public function loadCurrentShift()
    {
        $user_id = Auth::id();

        // Auto-heal stale records: shifts that were clocked out but status remained active.
        ShiftModel::where('employee_id', $user_id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNotNull('clock_out')
            ->update([
                'status' => 'closed',
                'workflow_state' => 'completed',
            ]);

        // Get today's active shift for this user
        $this->currentShift = ShiftModel::where('employee_id', $user_id)
            ->where('shift_date', Carbon::today())
            ->where('status', 'active')
            ->whereNull('clock_out')
            ->first();

        $this->hasActiveShift = $this->currentShift !== null;
    }

    public function clockIn()
    {
        // Validate the inputs
        $this->validate([
            'shift_config_id' => 'required|exists:shift_configurations,id',
        ], [
            'shift_config_id.required' => 'Please select a shift configuration',
            'shift_config_id.exists' => 'Invalid shift configuration selected',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        try {
            if (! $this->b_id) {
                $this->toast()->error('No branch selected. Please contact administrator.')->send();

                return;
            }

            // If the user has assigned shift types, they must clock in with those types
            [$assignedConfigIds, $assignedTypes] = $this->getAssignedShiftConstraints($user);
            if (! empty($assignedConfigIds) || ! empty($assignedTypes)) {
                $shiftConfig = ShiftConfiguration::findOrFail($this->shift_config_id);
                $normalizedType = strtolower((string) $shiftConfig->shift_type);

                $configAllowed = empty($assignedConfigIds) || in_array($shiftConfig->id, $assignedConfigIds, true);
                $typeAllowed = empty($assignedTypes) || in_array($normalizedType, $assignedTypes, true);

                if (! $configAllowed && ! $typeAllowed) {
                    $this->toast()->error('You are assigned to a specific shift for today. Please select your assigned shift.')->send();
                    return;
                }
            }

            // Get the Branch
            $branch = Branch::findOrFail($this->b_id);

            // Get the selected shift configuration
            $shiftConfig = ShiftConfiguration::findOrFail($this->shift_config_id);

            // Map the shift configuration's shift_type to a compatible enum value
            // This ensures compatibility with the database enum while using dynamic configurations
            $enumCompatibleShiftType = $this->getEnumCompatibleShiftType($shiftConfig->shift_type);

            if ($enumCompatibleShiftType === null) {
                $this->toast()->error('Invalid shift type selected. Please contact administrator.')->send();
                return;
            }

            // Set the shift type that's compatible with the database enum
            $this->shift_type = $enumCompatibleShiftType;

            // STEP 1: STRICT TIME WINDOW VALIDATION
            $timingValidator = app(ShiftTimingValidator::class);
            $now = Carbon::now($shiftConfig->timezone ?? config('app.timezone'));
            if (! $shiftConfig->isWithinStrictClockInWindow($now)) {
                $message = $shiftConfig->getClockInViolationMessage($now);
                $this->toast()->error($message)->send();

                \Log::warning('Clock-in time violation attempt', [
                    'employee_id' => $user->id,
                    'shift_type' => $this->shift_type,
                    'shift_config_id' => $this->shift_config_id,
                    'branch_id' => $this->b_id,
                    'requested_time' => $now->toDateTimeString(),
                    'violation_message' => $message,
                ]);

                return;
            }

            // STEP 2: Check for conflicting shifts
            $conflictValidation = $timingValidator->validateNoConflictingShifts(
                $user->id,
                $this->shift_type,
                $this->b_id
            );

            if (! $conflictValidation->isValid()) {
                $this->toast()->error($conflictValidation->getMessage())->send();

                return;
            }

            // Create a new shift
            $shift = $this->createShift($branch, $user, $shiftConfig);

            // Create SalesShift if user is in sales department
            $salesShift = null;
            if ($this->isSalesDepartment($user)) {
                $salesShift = $this->createSalesShift($branch, $user, $shift);
            }

            // Dispatch event to update header
            $this->dispatch('shift-updated');

            $this->toast()->success('Clocked in successfully! Have a productive shift!')->send();

            // Check for expired products if in sales department
            if ($salesShift) {
                return $this->checkExpiryAndRedirect($branch, $salesShift, $user);
            }

            return $this->redirectToDashboard($branch);

        } catch (\Exception $e) {
            \Log::error('Clock-in error', [
                'employee_id' => $user->id ?? null,
                'branch_id' => $this->b_id,
                'shift_config_id' => $this->shift_config_id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Error clocking in: '.$e->getMessage())->send();
        }
    }

    /**
     * Map the shift configuration's shift_type to a compatible enum value
     * This maintains compatibility with the database enum while allowing dynamic configurations
     */
    private function getEnumCompatibleShiftType(string $configShiftType): ?string
    {
        // Define mapping from configuration shift types to database enum values
        $mapping = [
            'morning' => 'morning',
            'afternoon' => 'afternoon',
            'night' => 'night',
            'full_time' => 'morning', // Map full_time to morning as fallback
            'evening' => 'afternoon', // Map evening to afternoon as fallback
            'midnight' => 'night', // Map midnight to night as fallback
            'custom' => 'morning', // Map custom to morning as fallback
        ];

        // Normalize the input to lowercase
        $normalizedType = strtolower($configShiftType);

        // Return mapped value or default to 'morning' if not found
        return $mapping[$normalizedType] ?? 'morning';
    }

    public function clockOut()
    {
        try {
            if (! $this->currentShift) {
                $this->toast()->error('No active shift found to clock out!')->send();

                return;
            }

            // Check if auto-clock-out is configured and apply it
            $autoClockOutMinutes = data_get($this->currentShift->metadata, 'auto_clock_out_minutes', 0);

            if ($autoClockOutMinutes > 0) {
                // Calculate expected end time from shift configuration
                $expectedEndTime = $this->resolveExpectedEndTime();

                // Calculate auto-clock-out time (end time + auto-clock-out minutes)
                $autoClockOutTime = $expectedEndTime->copy()->addMinutes($autoClockOutMinutes);

                // Use the later of current time or auto-clock-out time
                $actualClockOutTime = Carbon::now()->max($autoClockOutTime);

                // Update shift with calculated clock out time
                $this->currentShift->clock_out = $actualClockOutTime;
            } else {
                // Use current time for clock out
                $this->currentShift->clock_out = Carbon::now();
            }

            $this->currentShift->status = 'closed';
            $this->currentShift->save();

            // Calculate total hours worked
            $workedMinutes = (int) floor($this->currentShift->clock_in->diffInMinutes($this->currentShift->clock_out));
            $totalHours = (int) floor($workedMinutes / 60);
            $totalMinutes = $workedMinutes % 60;

            $this->toast()->success("Clocked out successfully! Total time: {$totalHours}h {$totalMinutes}m")->send();

            // Dispatch event to update header
            $this->dispatch('shift-updated');

            // Redirect to dashboard
            $branch = Branch::findOrFail($this->b_id);

            return $this->redirectToDashboard($branch);

        } catch (\Exception $e) {
            \Log::error('Clock-out error: '.$e->getMessage(), [
                'employee_id' => Auth::id(),
                'shift_id' => $this->currentShift->id ?? null,
            ]);
            $this->toast()->error('Error clocking out: '.$e->getMessage())->send();
        }
    }

    private function createShift(Branch $branch, $user, $shiftConfig)
    {
        $shiftNumber = ShiftModel::generateShiftNumber(
            (string) ($user->department?->slug ?? $user->department_id ?? 'dept'),
            (string) $user->id,
            Carbon::now(),
            (string) $this->shift_type
        );

        $shift = new ShiftModel;
        $shift->branch_id = $branch->id;
        $shift->employee_id = $user->id;
        // In unified system, department is determined by role, not stored in user
        $shift->department_id = $user->department_id ?? null;
        $shift->shift_number = $shiftNumber;
        $shift->shift_date = Carbon::today();
        $shift->shift_type = $this->shift_type; // Use enum-compatible type for DB
        $shift->clock_in = Carbon::now();
        $shift->clock_out = null;
        $shift->status = 'active';
        $shift->notes = $this->notes;

        // Store configuration reference for later use
        $shift->metadata = [
            'config_id' => $shiftConfig->id,
            'config_name' => $shiftConfig->name,
            'original_shift_type' => $shiftConfig->shift_type, // Store original type
            'mapped_shift_type' => $this->shift_type, // Store mapped type
            'start_time' => $shiftConfig->start_time,
            'end_time' => $shiftConfig->end_time,
            'expected_end' => $shiftConfig->end_time,
            'auto_clock_out_minutes' => $shiftConfig->auto_clock_out_minutes,
            'max_overtime_hours' => $shiftConfig->max_overtime_hours,
            'break_duration_minutes' => $shiftConfig->break_duration_minutes,
        ];

        $shift->save();

        $this->currentShift = $shift;
        $this->hasActiveShift = true;

        return $shift;
    }

    private function redirectToDashboard(Branch $branch)
    {
        return $this->redirectIntended(
            default: route('branch-dashboard.index', ['b_id' => $branch->id], absolute: false),
            navigate: true
        );
    }

    public function getTotalHoursWorked()
    {
        if (! $this->currentShift || ! $this->currentShift->clock_in) {
            return '0h 0m';
        }

        $endTime = $this->currentShift->clock_out ?? Carbon::now();
        $totalMinutes = (int) floor($this->currentShift->clock_in->diffInMinutes($endTime));
        $hours = (int) floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return "{$hours}h {$minutes}m";
    }

    public function continueToWork()
    {
        $branch = Branch::findOrFail($this->b_id);
        $user = Auth::user();

        // If user is still clocked in and belongs to sales, continue directly to POS.
        if ($this->hasActiveShift && $this->currentShift && $this->isSalesDepartment($user)) {
            $salesDeptSlug = $this->getSalesDepartmentSlugForUser($user);

            return $this->redirect(
                route('branch-dashboard.sales-dashboard.pos.index', [
                    'salesDeptSlug' => $salesDeptSlug,
                    'b_id' => $branch->id,
                ]),
                navigate: true
            );
        }

        return $this->redirectToDashboard($branch);
    }

    /**
     * Check if user is in sales department (based on role)
     */
    private function isSalesDepartment($user): bool
    {
        if (! $user) {
            return false;
        }

        $category = $user->department?->category?->name;
        if ($category === 'Sales') {
            return true;
        }

        if ($user->can('view-sales') || $user->can('process-sales')) {
            return true;
        }

        return \App\Services\SidebarVisibilityService::canSeeSalesManagement($user);
    }

    /**
     * Create a SalesShift record for sales department users
     */
    private function createSalesShift(Branch $branch, $user, ShiftModel $shift): SalesShift
    {
        $shiftNumber = SalesShift::generateShiftNumber(
            (string) ($shift->department?->slug ?? $shift->department_id ?? 'dept'),
            (string) $user->id,
            Carbon::now(),
            (string) $this->shift_type
        );

        $salesShift = SalesShift::create([
            'branch_id' => $branch->id,
            'department_id' => $shift->department_id ?? $this->getDepartmentIdForUser($user),
            'employee_id' => $user->id,
            'shift_number' => $shiftNumber,
            'shift_date' => Carbon::today(),
            'shift_type' => $this->shift_type,
            'clock_in' => Carbon::now(),
            'status' => 'active',
            'notes' => $this->notes,
        ]);

        return $salesShift;
    }

    /**
     * Check for expired products and redirect accordingly
     */
    private function checkExpiryAndRedirect(Branch $branch, SalesShift $salesShift, $user)
    {
        $service = new CheckExpiredProducts;

        // Get expired products (department determined by role in unified system)
        $departmentId = $this->getDepartmentIdForUser($user);
        $expiredProducts = $service->getExpiredProductsForShift(
            $salesShift->id,
            $branch->id,
            $departmentId
        );

        // If there are expired products, redirect to expiry alerts page
        if ($expiredProducts->isNotEmpty()) {
            return $this->redirect(
                route('branch-dashboard.sales-dashboard.expiry-alerts', [
                    'b_id' => $branch->id,
                    'salesShiftId' => $salesShift->id,
                ]),
                navigate: true
            );
        }

        // No expired products, proceed to dashboard
        return $this->redirectToDashboard($branch);
    }

    /**
     * Get department ID for user based on role (unified system)
     */
    private function getDepartmentIdForUser($user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->department_id) {
            return $user->department_id;
        }

        return \App\Models\Department::where('branch_id', $user->branch_id)
            ->whereHas('category', fn ($q) => $q->where('name', 'Sales'))
            ->value('id');
    }

    private function getSalesDepartmentSlugForUser($user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->department?->slug) {
            return $user->department->slug;
        }

        return \App\Models\Department::where('branch_id', $this->b_id)
            ->whereHas('category', fn ($q) => $q->where('name', 'Sales'))
            ->value('slug');
    }

    public function getAvailableShiftsProperty()
    {
        if (!$this->b_id) {
            return collect([]);
        }

        $query = ShiftConfiguration::where('branch_id', $this->b_id)
            ->orWhereNull('branch_id') // Include global configurations
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $user = Auth::user();
        if (! $user) {
            return $query;
        }

        [$assignedConfigIds, $assignedTypes] = $this->getAssignedShiftConstraints($user);
        if (empty($assignedConfigIds) && empty($assignedTypes)) {
            return $query;
        }

        return $query->filter(function ($config) use ($assignedConfigIds, $assignedTypes) {
            $configAllowed = empty($assignedConfigIds) || in_array($config->id, $assignedConfigIds, true);
            $typeAllowed = empty($assignedTypes) || in_array(strtolower((string) $config->shift_type), $assignedTypes, true);
            return $configAllowed || $typeAllowed;
        })->values();
    }

    public function render()
    {
        return view('livewire.auth.shift', [
            'availableShifts' => $this->availableShifts
        ]);
    }

    private function resolveExpectedEndTime(): Carbon
    {
        $shiftDate = $this->currentShift->shift_date instanceof Carbon
            ? $this->currentShift->shift_date->toDateString()
            : Carbon::parse($this->currentShift->shift_date)->toDateString();

        $endTimeRaw = (string) data_get($this->currentShift->metadata, 'end_time', '23:59:59');

        try {
            // Plain clock time (HH:MM or HH:MM:SS)
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTimeRaw) === 1) {
                return Carbon::parse($shiftDate . ' ' . $endTimeRaw);
            }

            // Full datetime/ISO value
            return Carbon::parse($endTimeRaw);
        } catch (\Throwable $e) {
            return Carbon::parse($shiftDate . ' 23:59:59');
        }
    }

    /**
     * Returns [assignedConfigIds, assignedShiftTypes] for any scheduled shifts.
     */
    private function getAssignedShiftConstraints($user): array
    {
        if (! $user) {
            return [[], []];
        }

        $assignedShifts = ShiftModel::where('employee_id', $user->id)
            ->whereIn('status', ['scheduled', 'active'])
            ->get();

        if ($assignedShifts->isEmpty()) {
            return [[], []];
        }

        $configIds = [];
        $types = [];

        foreach ($assignedShifts as $shift) {
            $configId = data_get($shift->metadata, 'config_id');
            if ($configId) {
                $configIds[] = (int) $configId;
            } elseif (! empty($shift->shift_type)) {
                $types[] = strtolower((string) $shift->shift_type);
            }
        }

        return [array_values(array_unique($configIds)), array_values(array_unique($types))];
    }
}

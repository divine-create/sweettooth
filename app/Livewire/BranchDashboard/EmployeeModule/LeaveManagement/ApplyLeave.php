<?php

namespace App\Livewire\BranchDashboard\EmployeeModule\LeaveManagement;

use App\Livewire\BaseComponent;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\LeaveAuditService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class ApplyLeave extends BaseComponent
{
    use Interactions, WithFileUploads;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Form fields
    public $leave_type_id = null;

    public $start_date = '';

    public $end_date = '';

    public $reason = '';

    public $emergency_contact = '';

    public $supporting_document = null;

    // Calculated fields
    public $total_days = 0;

    public $available_balance = 0;

    public $selected_leave_type = null;

    protected function getModelClass(): string
    {
        return LeaveApplication::class;
    }

    public function getAllSelectableIds(): array
    {
        return [];
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->initializeLeaveBalances();
    }

    protected function initializeLeaveBalances()
    {
        $employee = auth()->user();
        $currentYear = now()->year;

        // Initialize leave balances for current year if not exists
        EmployeeLeaveBalance::initializeForEmployee($employee->id, $currentYear);
    }

    public function updatedLeaveTypeId($value)
    {
        if ($value) {
            $this->selected_leave_type = LeaveType::find($value);
            $this->updateAvailableBalance();
        } else {
            $this->selected_leave_type = null;
            $this->available_balance = 0;
        }
    }

    public function updatedStartDate()
    {
        $this->calculateTotalDays();
    }

    public function updatedEndDate()
    {
        $this->calculateTotalDays();
    }

    protected function calculateTotalDays()
    {
        if ($this->start_date && $this->end_date) {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);

            if ($end->lt($start)) {
                $this->total_days = 0;

                return;
            }

            $workingDays = 0;
            $current = $start->copy();

            while ($current->lte($end)) {
                // Skip weekends
                if ($current->dayOfWeek !== Carbon::SATURDAY && $current->dayOfWeek !== Carbon::SUNDAY) {
                    $workingDays++;
                }
                $current->addDay();
            }

            $this->total_days = $workingDays;
        }
    }

    protected function updateAvailableBalance()
    {
        if (! $this->leave_type_id) {
            $this->available_balance = 0;

            return;
        }

        $employee = auth()->user();
        $currentYear = now()->year;

        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $currentYear)
            ->first();

        $this->available_balance = $balance ? $balance->remaining_days : 0;
    }

    public function submit()
    {
        $employee = auth()->user();

        $this->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10',
            'emergency_contact' => 'nullable|string',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            // Recalculate total days
            $this->calculateTotalDays();

            // Validate leave balance
            if ($this->total_days > $this->available_balance) {
                $this->toast()->error("Insufficient leave balance. You have {$this->available_balance} days remaining.")->send();

                return;
            }

            // Check max consecutive days (calendar days, not working days)
            if ($this->selected_leave_type && $this->selected_leave_type->max_consecutive_days) {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);
                $consecutiveDays = $start->diffInDays($end) + 1; // +1 to include both start and end date

                if ($this->calculateTotalDays() > $consecutiveDays) {
                    $this->toast()->error("Maximum consecutive calendar days for this leave type is {$this->selected_leave_type->max_consecutive_days} days. Your request spans {$consecutiveDays} calendar days.")->send();

                    return;
                }
            }

            // Check minimum notice period
            if ($this->selected_leave_type && $this->selected_leave_type->min_notice_days > 0) {

                $startDate = Carbon::parse($this->start_date)->startOfDay();
                $today = now()->startOfDay();
                $noticeGiven = $today->diffInDays($startDate, false); // false = allow negative

                if ($noticeGiven < $this->selected_leave_type->min_notice_days) {
                    $this->toast()->error("Minimum notice period of {$this->selected_leave_type->min_notice_days} days required. You applied only $noticeGiven days in advance.")->send();

                    return;
                }
            }

            // Check if document is required
            if ($this->selected_leave_type && $this->selected_leave_type->requires_document && ! $this->supporting_document) {
                $this->toast()->error('Supporting document is required for this leave type.')->send();

                return;
            }

            // Handle file upload
            $documentPath = null;
            if ($this->supporting_document) {
                $documentPath = $this->supporting_document->store('leave-documents', 'public');
            }

            // Create leave application
            $leaveApplication = LeaveApplication::create([
                'application_number' => LeaveApplication::generateApplicationNumber(),
                'employee_id' => $employee->id,
                'leave_type_id' => $this->leave_type_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'total_days' => $this->total_days,
                'reason' => $this->reason,
                'emergency_contact' => $this->emergency_contact,
                'supporting_document' => $documentPath,
                'status' => 'pending',
            ]);

            // Log the leave application creation
            LeaveAuditService::logLeaveApplication(
                $leaveApplication,
                $employee,
                'pending'
            );

            // Update leave balance (mark as pending)
            $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $this->leave_type_id)
                ->where('year', Carbon::parse($this->start_date)->year)
                ->first();

            if ($balance) {
                $balance->pending_days += $this->total_days;
                $balance->updateBalance();
            }

            $this->resetForm();
            $this->redirect(branch_route('leave.my-leaves', ['b_id' => $this->getBranchId()]), navigate: true);
            $this->dispatch('leave-application-success', message: "Leave application {$leaveApplication->application_number} submitted successfully!");
        } catch (\Exception $e) {
            $this->toast()->error('Error submitting leave application: '.$e->getMessage())->send();
        }
    }

    protected function resetForm()
    {
        $this->leave_type_id = null;
        $this->start_date = '';
        $this->end_date = '';
        $this->reason = '';
        $this->emergency_contact = '';
        $this->supporting_document = null;
        $this->total_days = 0;
        $this->available_balance = 0;
        $this->selected_leave_type = null;
    }

    public function render()
    {
        $leaveTypes = LeaveType::active()->get();
        $employee = auth()->user();
        $currentYear = now()->year;

        // Get all leave balances for current employee
        $leaveBalances = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get();

        return view('livewire.branch-dashboard.employee-module.leave-management.apply-leave', [
            'leaveTypes' => $leaveTypes,
            'leaveBalances' => $leaveBalances,
        ]);
    }
}

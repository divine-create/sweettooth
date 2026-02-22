<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Services\SalesWorkflowService;

class WorkflowProgress extends Component
{
    public ?int $employeeId = null;
    public ?int $shiftId = null;

    protected $listeners = ['workflow-state-changed' => '$refresh', 'shift-updated' => '$refresh'];

    public function mount(?int $employeeId = null, ?int $shiftId = null)
    {
        $this->employeeId = $employeeId;
        $this->shiftId = $shiftId;
    }

    public function getProgressProperty(): ?array
    {
        // Super admins don't need workflow progress
        if (is_super_admin() || can_access_all_branches()) {
            return null;
        }

        if (!$this->employeeId || !$this->shiftId) {
            return null;
        }

        $workflowService = app(SalesWorkflowService::class);
        $currentState = $workflowService->getCurrentState($this->employeeId, $this->shiftId);

        return [
            'current_state' => $currentState,
            'steps' => $workflowService->getWorkflowSteps($this->employeeId, $this->shiftId),
            'percentage_complete' => $workflowService->getProgressPercentage($this->employeeId, $this->shiftId),
            'next_action' => $workflowService->getNextRequiredAction($this->employeeId, $this->shiftId)
        ];
    }

    public function render()
    {
        return view('livewire.components.workflow-progress');
    }
}

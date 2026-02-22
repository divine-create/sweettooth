<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Models\GlobalBranchManagement;
use Livewire\Component;

class BranchManagement extends Component
{
    public $warehouseAdd;
    public $branchEdit;
    public $branchDelete;
    public $interBranchTransfer;
    public $centralWarehouse;
    public $branchAdminAssign;
    public $branchHours;
    public $saasTenant;

    protected $rules = [
        'warehouseAdd' => 'boolean',
        'branchEdit' => 'boolean',
        'branchDelete' => 'boolean',
        'interBranchTransfer' => 'boolean',
        'centralWarehouse' => 'boolean',
        'branchAdminAssign' => 'boolean',
        'branchHours' => 'boolean',
        'saasTenant' => 'boolean',
    ];

    public function mount()
    {
        $settings = GlobalBranchManagement::first();

        if ($settings) {
            $this->warehouseAdd = $settings->warehouse_add;
            $this->branchEdit = $settings->branch_edit;
            $this->branchDelete = $settings->branch_delete;
            $this->interBranchTransfer = $settings->inter_branch_transfer;
            $this->centralWarehouse = $settings->central_warehouse;
            $this->branchAdminAssign = $settings->branch_admin_assign;
            $this->branchHours = $settings->branch_hours;
            $this->saasTenant = $settings->saas_tenant;
        } else {
            $this->warehouseAdd = false;
            $this->branchEdit = false;
            $this->branchDelete = false;
            $this->interBranchTransfer = false;
            $this->centralWarehouse = false;
            $this->branchAdminAssign = false;
            $this->branchHours = false;
            $this->saasTenant = false;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $settings = GlobalBranchManagement::first();

            if (!$settings) {
                $settings = new GlobalBranchManagement();
            }

            $settings->warehouse_add = $this->warehouseAdd;
            $settings->branch_edit = $this->branchEdit;
            $settings->branch_delete = $this->branchDelete;
            $settings->inter_branch_transfer = $this->interBranchTransfer;
            $settings->central_warehouse = $this->centralWarehouse;
            $settings->branch_admin_assign = $this->branchAdminAssign;
            $settings->branch_hours = $this->branchHours;
            $settings->saas_tenant = $this->saasTenant;

            $settings->save();

            session()->flash('message', 'Branch Management settings saved successfully!');
        } catch (\Exception $e) {
            \Log::error('Failed to save branch management settings: ' . $e->getMessage());
            session()->flash('error', 'Failed to save branch management settings. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.branch-management');
    }
}
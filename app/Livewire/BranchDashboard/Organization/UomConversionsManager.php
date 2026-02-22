<?php

namespace App\Livewire\BranchDashboard\Organization;

use App\Livewire\BranchDashboard\Settings\UomConversions as BaseUomConversions;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class UomConversionsManager extends BaseUomConversions
{
    public function mount(): void
    {
        if (! is_super_admin() && ! has_permission('manage-organization')) {
            abort(403, 'You do not have access to manage UOM conversions.');
        }
    }
}


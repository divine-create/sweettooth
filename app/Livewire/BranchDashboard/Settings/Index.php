<?php

namespace App\Livewire\BranchDashboard\Settings;

use Livewire\Component;

class Index extends Component
{
    public $activeTab = 'business-config';

    public $tabs = [
        ['id' => 'business-config', 'name' => 'Business Configuration'],
        ['id' => 'currency-localization', 'name' => 'Currency & Localization'],
        ['id' => 'appearance', 'name' => 'Appearance & Theme'],
        ['id' => 'security-approvals', 'name' => 'Security & Approvals'],
    ];

    public function mount()
    {
        // Check if user is super admin
        if (!is_super_admin()) {
            abort(403, 'Only Super Admins can access settings');
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.index')->layout('components.layouts.app.branch-dashboard');
    }
}

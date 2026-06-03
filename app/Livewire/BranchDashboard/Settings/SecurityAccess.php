<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Helpers\Settings;
use App\Models\GlobalSecurityAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SecurityAccess extends Component
{
    public string $authentication = '2fa';

    public bool $auditLogs = true;

    public bool $approvalRequired = true;

    public string $dataIsolation = 'saas_company';

    public function mount(): void
    {
        if (! is_super_admin()) {
            abort(403, 'Only Super Admins can manage security settings');
        }

        $settings = GlobalSecurityAccess::first();

        $this->authentication = $settings->authentication ?? '2fa';
        $this->auditLogs = filter_var($settings->audit_logs ?? 'enabled', FILTER_VALIDATE_BOOLEAN)
            || in_array($settings->audit_logs ?? 'enabled', ['enabled', '1', 1, true], true);
        $this->approvalRequired = (bool) ($settings->approval_required ?? true);
        $this->dataIsolation = $settings->data_isolation ?? 'saas_company';
    }

    public function save(): void
    {
        if (! is_super_admin()) {
            abort(403);
        }

        $this->validate([
            'authentication' => 'required|string|in:password,2fa,sso',
            'dataIsolation' => 'required|string|in:saas_company,branch,user',
        ]);

        $settings = GlobalSecurityAccess::first() ?? new GlobalSecurityAccess();

        $settings->authentication = $this->authentication;
        $settings->audit_logs = $this->auditLogs ? 'enabled' : 'disabled';
        $settings->approval_required = $this->approvalRequired;
        $settings->data_isolation = $this->dataIsolation;
        $settings->save();

        // Invalidate the cached settings so the new approval state takes effect immediately.
        Settings::clearCache();

        session()->flash('message', 'Security & approval settings updated successfully.');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.security-access');
    }
}

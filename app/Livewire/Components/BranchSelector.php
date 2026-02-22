<?php

namespace App\Livewire\Components;

use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;

class BranchSelector extends Component
{
    public ?string $selectedBranch = null;

    public function mount()
    {
        // Set selected branch from session/context
        $this->selectedBranch = current_branch_id();
    }

    #[Computed]
    public function isSuperAdmin()
    {
        return is_super_admin();
    }

    #[Computed]
    public function branches()
    {
        if (!$this->isSuperAdmin()) {
            return collect([]);
        }

        return get_accessible_branches();
    }

    #[Computed]
    public function currentBranchName()
    {
        if (!$this->selectedBranch) {
            return 'No branch selected';
        }

        $branch = $this->branches()->firstWhere('id', $this->selectedBranch);
        return $branch?->name ?? 'No branch selected';
    }

    public function changeBranch()
    {
        $branchId = $this->selectedBranch;

        // Skip if empty value (placeholder selected)
        if (empty($branchId)) {
            return;
        }

        // Validate branch access
        if (!validate_branch_access($branchId)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'You do not have access to this branch.'
            ]);
            // Reset to current branch
            $this->selectedBranch = current_branch_id();
            return;
        }

        // Set the branch context
        set_current_branch($branchId, updateUserPreference: true);

        // Emit event to notify all components that branch has changed
        $this->dispatch('branch-changed', branchId: $branchId);

        // Show success message
        $branch = $this->branches()->firstWhere('id', $branchId);
        $branchName = $branch?->name ?? 'Unknown';

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => "Switched to {$branchName} branch."
        ]);

        // Get the original page URL from referer (not Livewire endpoint)
        $referer = request()->header('Referer');
        $parsedUrl = parse_url($referer);
        $currentPath = $parsedUrl['path'] ?? '/';

        // Parse existing query parameters
        parse_str($parsedUrl['query'] ?? '', $existingParams);

        // Merge with new b_id
        $queryParams = array_merge($existingParams, ['b_id' => $branchId]);

        // Build the full redirect URL
        $redirectUrl = $currentPath . '?' . http_build_query($queryParams);

        // Use Livewire redirect
        $this->redirect($redirectUrl, navigate: false);
    }

    public function render()
    {
        return view('livewire.components.branch-selector');
    }
}

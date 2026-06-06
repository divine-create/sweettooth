<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Branch;
use App\Models\Supplier;
use App\Services\AuditService;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

class CreateSupplier extends Component
{
    use Interactions;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $website = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $postalCode = '';
    public string $country = 'Kenya';
    public string $taxId = '';
    public string $status = 'active';
    public string $creditLimit = '';
    public string $paymentTermsDays = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name'             => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'website'          => 'nullable|url|max:255',
            'address'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'state'            => 'nullable|string|max:100',
            'postalCode'       => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:100',
            'taxId'            => 'nullable|string|max:50',
            'status'           => 'nullable|in:active,inactive,blacklisted',
            'creditLimit'      => 'nullable|numeric|min:0',
            'paymentTermsDays' => 'nullable|integer|min:0',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'             => 'Supplier Name',
            'email'            => 'Email Address',
            'phone'            => 'Phone Number',
            'website'          => 'Website',
            'address'          => 'Street Address',
            'city'             => 'City',
            'state'            => 'State',
            'postalCode'       => 'Postal Code',
            'country'          => 'Country',
            'taxId'            => 'Tax ID',
            'status'           => 'Status',
            'creditLimit'      => 'Credit Limit',
            'paymentTermsDays' => 'Payment Terms (Days)',
            'notes'            => 'Notes',
        ];
    }

    private function generateCode(): string
    {
        $branchId = current_branch_id();
        $branch = Branch::find($branchId);

        $prefix = 'SUP';
        if ($branch?->name) {
            $slug = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $branch->name));
            $prefix .= '-' . substr($slug, 0, 3);
        }

        $sequence = Supplier::where('branch_id', $branchId)->count() + 1;

        do {
            $code = $prefix . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Supplier::where('code', $code)->exists());

        return $code;
    }

    public function createSupplier(): void
    {
        $validated = $this->validate();

        $supplier = Supplier::create([
            'branch_id'          => current_branch_id(),
            'created_by'         => auth()->id(),
            'code'               => $this->generateCode(),
            'name'               => $validated['name'] ?? '',
            'email'              => ($validated['email'] ?? '') ?: null,
            'phone'              => ($validated['phone'] ?? '') ?: null,
            'website'            => ($validated['website'] ?? '') ?: null,
            'address'            => ($validated['address'] ?? '') ?: null,
            'city'               => ($validated['city'] ?? '') ?: null,
            'state'              => ($validated['state'] ?? '') ?: null,
            'postal_code'        => ($validated['postalCode'] ?? '') ?: null,
            'country'            => ($validated['country'] ?? '') ?: null,
            'tax_id'             => ($validated['taxId'] ?? '') ?: null,
            'status'             => ($validated['status'] ?? '') ?: 'active',
            'credit_limit'       => (float) ($validated['creditLimit'] ?: 0),
            'payment_terms_days' => (int) ($validated['paymentTermsDays'] ?: 30),
            'notes'              => $validated['notes'] ?? null,
        ]);

        AuditService::log(
            current_actor(),
            'create',
            $supplier,
            "Created supplier '{$supplier->name}' (code: {$supplier->code})",
            'completed'
        );

        $this->resetForm();
        $this->dispatch('supplierCreated');
    }

    public function resetForm(): void
    {
        $this->reset([
            'name', 'email', 'phone', 'website', 'address',
            'city', 'state', 'postalCode', 'taxId', 'creditLimit',
            'paymentTermsDays', 'notes',
        ]);
        $this->country = 'Kenya';
        $this->status = 'active';
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.create-supplier');
    }
}

<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use Livewire\Component;

class CreateSupplier extends Component
{
    public string $code = '';
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
    public string $status = 'pending';
    public string $creditLimit = '';
    public string $paymentTermsDays = '30';
    public string $notes = '';
    public bool $showSuccessMessage = false;

    protected function rules()
    {
        return [
            'code' => 'required|unique:suppliers,code|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postalCode' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'taxId' => 'required|string|max:50|unique:suppliers,tax_id',
            'creditLimit' => 'required|numeric|min:0',
            'paymentTermsDays' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'code' => 'Supplier Code',
            'name' => 'Supplier Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'website' => 'Website',
            'address' => 'Street Address',
            'city' => 'City',
            'state' => 'State',
            'postalCode' => 'Postal Code',
            'country' => 'Country',
            'taxId' => 'Tax ID',
            'creditLimit' => 'Credit Limit',
            'paymentTermsDays' => 'Payment Terms (Days)',
            'notes' => 'Notes',
        ];
    }

    public function createSupplier(): void
    {
        $validated = $this->validate();

        Supplier::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'website' => $validated['website'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postalCode'],
            'country' => $validated['country'],
            'tax_id' => $validated['taxId'],
            'credit_limit' => $validated['creditLimit'],
            'payment_terms_days' => $validated['paymentTermsDays'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'created_by' => auth()->id(),
        ]);

        $this->showSuccessMessage = true;
        $this->resetForm();

        $this->dispatch('supplierCreated');

        // Auto-hide success message after 3 seconds
        $this->dispatch('hideSuccessMessage')->later(3000);
    }

    public function resetForm(): void
    {
        $this->reset([
            'code', 'name', 'email', 'phone', 'website', 'address',
            'city', 'state', 'postalCode', 'country', 'taxId',
            'status', 'creditLimit', 'paymentTermsDays', 'notes'
        ]);
        $this->country = 'Kenya';
        $this->status = 'pending';
        $this->paymentTermsDays = '30';
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.create-supplier');
    }
}

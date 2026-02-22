<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Livewire\Component;

class CreateSupplierBankAccount extends Component
{
    public Supplier $supplier;
    public string $bankName = '';
    public string $accountNumber = '';
    public string $accountHolderName = '';
    public string $accountType = 'checking';
    public string $swiftCode = '';
    public bool $isPrimary = false;

    protected function rules()
    {
        return [
            'bankName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:50',
            'accountHolderName' => 'required|string|max:255',
            'accountType' => 'required|in:checking,savings,business',
            'swiftCode' => 'nullable|string|max:20',
            'isPrimary' => 'boolean',
        ];
    }

    public function createBankAccount(): void
    {
        $validated = $this->validate();

        // If setting as primary, unset other primary accounts
        if ($validated['isPrimary']) {
            $this->supplier->bankAccounts()->update(['is_primary' => false]);
        }

        SupplierBankAccount::create([
            'supplier_id' => $this->supplier->id,
            'bank_name' => $validated['bankName'],
            'account_number' => $validated['accountNumber'],
            'account_holder_name' => $validated['accountHolderName'],
            'account_type' => $validated['accountType'],
            'swift_code' => $validated['swiftCode'],
            'is_primary' => $validated['isPrimary'],
            'is_active' => true,
        ]);

        session()->flash('success', 'Bank account added successfully');
        $this->resetForm();
        $this->dispatch('accountCreated');
    }

    public function resetForm(): void
    {
        $this->reset(['bankName', 'accountNumber', 'accountHolderName', 'swiftCode', 'isPrimary']);
        $this->accountType = 'checking';
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.create-supplier-bank-account');
    }
}

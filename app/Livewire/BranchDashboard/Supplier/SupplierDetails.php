<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use Livewire\Component;

class SupplierDetails extends Component
{
    public ?Supplier $supplier = null;
    public string $activeTab = 'overview';
    public bool $showContactForm = false;
    public bool $showBankAccountForm = false;
    public bool $showPerformanceForm = false;
    public bool $editMode = false;

    // Edit form properties
    public string $editName = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public string $editWebsite = '';
    public string $editAddress = '';
    public string $editCity = '';
    public string $editState = '';
    public string $editPostalCode = '';
    public string $editCountry = '';
    public string $editTaxId = '';
    public string $editStatus = '';
    public string $editCreditLimit = '';
    public string $editPaymentTermsDays = '';
    public string $editNotes = '';

    #[On('supplierUpdated')]
    public function refreshSupplier(): void
    {
        $this->supplier?->refresh();
    }

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier->load([
            'contacts',
            'bankAccounts',
            'documents',
            'performanceHistory',
            'purchases',
        ]);
    }

    public function toggleEditMode(): void
    {
        if (!$this->editMode) {
            $this->initializeEditForm();
            $this->editMode = true;
        } else {
            $this->editMode = false;
        }
    }

    private function initializeEditForm(): void
    {
        $this->editName = $this->supplier->name;
        $this->editEmail = $this->supplier->email;
        $this->editPhone = $this->supplier->phone;
        $this->editWebsite = $this->supplier->website ?? '';
        $this->editAddress = $this->supplier->address;
        $this->editCity = $this->supplier->city;
        $this->editState = $this->supplier->state;
        $this->editPostalCode = $this->supplier->postal_code;
        $this->editCountry = $this->supplier->country;
        $this->editTaxId = $this->supplier->tax_id;
        $this->editStatus = $this->supplier->status;
        $this->editCreditLimit = (string) $this->supplier->credit_limit;
        $this->editPaymentTermsDays = (string) $this->supplier->payment_terms_days;
        $this->editNotes = $this->supplier->notes ?? '';
    }

    public function saveChanges(): void
    {
        $validated = $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:suppliers,email,' . $this->supplier->id,
            'editPhone' => 'required|string|max:20',
            'editWebsite' => 'nullable|url|max:255',
            'editAddress' => 'required|string|max:255',
            'editCity' => 'required|string|max:100',
            'editState' => 'required|string|max:100',
            'editPostalCode' => 'required|string|max:20',
            'editCountry' => 'required|string|max:100',
            'editTaxId' => 'required|string|max:50|unique:suppliers,tax_id,' . $this->supplier->id,
            'editStatus' => 'required|in:active,inactive,suspended,pending',
            'editCreditLimit' => 'required|numeric|min:0',
            'editPaymentTermsDays' => 'required|numeric|min:0',
            'editNotes' => 'nullable|string|max:1000',
        ]);

        $this->supplier->update([
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
            'phone' => $validated['editPhone'],
            'website' => $validated['editWebsite'],
            'address' => $validated['editAddress'],
            'city' => $validated['editCity'],
            'state' => $validated['editState'],
            'postal_code' => $validated['editPostalCode'],
            'country' => $validated['editCountry'],
            'tax_id' => $validated['editTaxId'],
            'status' => $validated['editStatus'],
            'credit_limit' => $validated['editCreditLimit'],
            'payment_terms_days' => $validated['editPaymentTermsDays'],
            'notes' => $validated['editNotes'],
        ]);

        $this->editMode = false;
        session()->flash('success', 'Supplier updated successfully');
        $this->dispatch('supplierUpdated');
    }

    public function deleteContact($contactId): void
    {
        $this->supplier->contacts()->find($contactId)?->delete();
        $this->supplier->refresh();
    }

    public function deleteBankAccount($accountId): void
    {
        $this->supplier->bankAccounts()->find($accountId)?->delete();
        $this->supplier->refresh();
    }

    public function deleteDocument($documentId): void
    {
        $this->supplier->documents()->find($documentId)?->delete();
        $this->supplier->refresh();
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->supplier->status) {
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
            'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->supplier->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending' => 'Pending Verification',
            default => ucfirst($this->supplier->status),
        };
    }

    public function getCreditUtilizationPercentage(): float
    {
        return $this->supplier->getCreditUtilizationPercentage();
    }

    public function getAvailableCredit(): float
    {
        return $this->supplier->getAvailableCredit();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.supplier-details');
    }
}

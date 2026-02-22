<?php

namespace App\Livewire\BranchDashboard\Supplier;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Livewire\Component;

class CreateSupplierContact extends Component
{
    public Supplier $supplier;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $role = '';
    public bool $isPrimary = false;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'role' => 'required|string|max:100',
            'isPrimary' => 'boolean',
        ];
    }

    public function createContact(): void
    {
        $validated = $this->validate();

        // If setting as primary, unset other primary contacts
        if ($validated['isPrimary']) {
            $this->supplier->contacts()->update(['is_primary' => false]);
        }

        SupplierContact::create([
            'supplier_id' => $this->supplier->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'is_primary' => $validated['isPrimary'],
            'is_active' => true,
        ]);

        session()->flash('success', 'Contact added successfully');
        $this->resetForm();
        $this->dispatch('contactCreated');
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'email', 'phone', 'role', 'isPrimary']);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.supplier.create-supplier-contact');
    }
}

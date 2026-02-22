<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierContactController extends Controller
{
    /**
     * Display contacts for a supplier.
     */
    public function index(Supplier $supplier): View
    {
        $contacts = $supplier->contacts()->paginate(10);

        return view('suppliers.contacts.index', compact('supplier', 'contacts'));
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create(Supplier $supplier): View
    {
        $roles = ['procurement', 'billing', 'delivery', 'other'];

        return view('suppliers.contacts.create', compact('supplier', 'roles'));
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:procurement,billing,delivery,other',
            'is_primary' => 'boolean',
        ]);

        // If setting as primary, unset other primary contacts
        if ($validated['is_primary'] ?? false) {
            $supplier->contacts()->update(['is_primary' => false]);
        }

        $supplier->contacts()->create($validated);

        return redirect()->route('suppliers.contacts.index', $supplier)
            ->with('success', 'Contact added successfully.');
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Supplier $supplier, SupplierContact $contact): View
    {
        $roles = ['procurement', 'billing', 'delivery', 'other'];

        return view('suppliers.contacts.edit', compact('supplier', 'contact', 'roles'));
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, Supplier $supplier, SupplierContact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:procurement,billing,delivery,other',
            'is_primary' => 'boolean',
        ]);

        // If setting as primary, unset other primary contacts
        if ($validated['is_primary'] ?? false) {
            $supplier->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return redirect()->route('suppliers.contacts.index', $supplier)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Supplier $supplier, SupplierContact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('suppliers.contacts.index', $supplier)
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Set contact as primary.
     */
    public function setPrimary(Supplier $supplier, SupplierContact $contact): RedirectResponse
    {
        $supplier->contacts()->update(['is_primary' => false]);
        $contact->update(['is_primary' => true]);

        return redirect()->route('suppliers.contacts.index', $supplier)
            ->with('success', 'Primary contact set successfully.');
    }

    /**
     * Get contacts by role (AJAX).
     */
    public function getByRole(Supplier $supplier, Request $request)
    {
        $role = $request->get('role');

        $contacts = $supplier->contacts()
            ->where('role', $role)
            ->active()
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($contacts);
    }

    /**
     * Get primary contact (AJAX).
     */
    public function getPrimary(Supplier $supplier)
    {
        $contact = $supplier->getActivePrimaryContact();

        if (!$contact) {
            return response()->json(['message' => 'No primary contact found'], 404);
        }

        return response()->json([
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'role' => $contact->role,
        ]);
    }
}

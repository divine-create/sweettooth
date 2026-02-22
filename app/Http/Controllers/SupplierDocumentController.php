<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class SupplierDocumentController extends Controller
{
    /**
     * Display documents for a supplier.
     */
    public function index(Supplier $supplier): View
    {
        $documents = $supplier->documents()
            ->with('verifiedBy')
            ->paginate(10);

        $expiringDocuments = $supplier->getExpiringDocuments();
        $expiredDocuments = $supplier->getExpiredDocuments();

        return view('suppliers.documents.index', compact(
            'supplier',
            'documents',
            'expiringDocuments',
            'expiredDocuments'
        ));
    }

    /**
     * Show the form for uploading a new document.
     */
    public function create(Supplier $supplier): View
    {
        $documentTypes = ['certificate', 'agreement', 'tax_doc', 'insurance', 'banking', 'other'];

        return view('suppliers.documents.create', compact('supplier', 'documentTypes'));
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:certificate,agreement,tax_doc,insurance,banking,other',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120', // 5MB max
            'expiry_date' => 'nullable|date|after:today',
        ]);

        // Store file
        $filePath = $request->file('file')->store("supplier-documents/{$supplier->id}", 'private');

        // Create document record
        $supplier->documents()->create([
            'document_type' => $validated['document_type'],
            'document_name' => $validated['document_name'],
            'file_path' => $filePath,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'is_verified' => false,
        ]);

        return redirect()->route('suppliers.documents.index', $supplier)
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Download a document.
     */
    public function download(Supplier $supplier, SupplierDocument $document)
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download($document->file_path, $document->document_name);
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Supplier $supplier, SupplierDocument $document): View
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        $documentTypes = ['certificate', 'agreement', 'tax_doc', 'insurance', 'banking', 'other'];

        return view('suppliers.documents.edit', compact('supplier', 'document', 'documentTypes'));
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, Supplier $supplier, SupplierDocument $document): RedirectResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'document_type' => 'required|in:certificate,agreement,tax_doc,insurance,banking,other',
            'document_name' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        // If new file uploaded, delete old one and store new
        if ($request->hasFile('file')) {
            Storage::disk('private')->delete($document->file_path);
            $filePath = $request->file('file')->store("supplier-documents/{$supplier->id}", 'private');
            $validated['file_path'] = $filePath;
        }

        $document->update($validated);

        return redirect()->route('suppliers.documents.index', $supplier)
            ->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Supplier $supplier, SupplierDocument $document): RedirectResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return redirect()->route('suppliers.documents.index', $supplier)
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Verify a document.
     */
    public function verify(Supplier $supplier, SupplierDocument $document): RedirectResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        $document->markAsVerified(auth()->id());

        return redirect()->route('suppliers.documents.index', $supplier)
            ->with('success', 'Document marked as verified.');
    }

    /**
     * Unverify a document.
     */
    public function unverify(Supplier $supplier, SupplierDocument $document): RedirectResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(403, 'Unauthorized');
        }

        $document->markAsUnverified();

        return redirect()->route('suppliers.documents.index', $supplier)
            ->with('success', 'Document marked as unverified.');
    }

    /**
     * Get expiring documents (AJAX).
     */
    public function getExpiring(Supplier $supplier)
    {
        $documents = $supplier->getExpiringDocuments()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_name' => $doc->document_name,
                    'document_type' => $doc->document_type,
                    'expiry_date' => $doc->expiry_date->format('Y-m-d'),
                    'days_until_expiry' => $doc->getDaysUntilExpiry(),
                ];
            });

        return response()->json($documents);
    }

    /**
     * Get document status summary.
     */
    public function getStatus(Supplier $supplier)
    {
        $allDocs = $supplier->documents()->count();
        $verifiedDocs = $supplier->documents()->verified()->count();
        $expiringDocs = $supplier->getExpiringDocuments()->count();
        $expiredDocs = $supplier->getExpiredDocuments()->count();

        return response()->json([
            'total' => $allDocs,
            'verified' => $verifiedDocs,
            'verification_percentage' => $allDocs > 0 ? round(($verifiedDocs / $allDocs) * 100) : 0,
            'expiring_soon' => $expiringDocs,
            'expired' => $expiredDocs,
            'compliance_status' => $expiredDocs === 0 ? 'compliant' : 'non_compliant',
        ]);
    }
}

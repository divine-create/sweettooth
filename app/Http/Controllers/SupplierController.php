<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): View
    {
        $query = Supplier::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Branch filter
        if ($request->has('branch_id') && $request->branch_id) {
            $query->byBranch($request->branch_id);
        }

        // Verification filter
        if ($request->has('is_verified')) {
            $query->where('is_verified', (bool)$request->is_verified);
        }

        // Outstanding balance filter
        if ($request->has('outstanding') && $request->outstanding === 'yes') {
            $query->withOutstanding();
        }

        $suppliers = $query->with('branch', 'contacts', 'performanceHistory')
            ->paginate(15)
            ->appends($request->query());

        $branches = Branch::active()->get();

        return view('suppliers.index', compact('suppliers', 'branches'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): View
    {
        $branches = Branch::active()->get();
        $statuses = ['active', 'inactive', 'blacklisted'];
        $creditRatings = ['excellent', 'good', 'fair', 'poor'];

        return view('suppliers.create', compact('branches', 'statuses', 'creditRatings'));
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:suppliers',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:suppliers',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,blacklisted',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $validated['created_by'] = auth()->id();

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'contacts',
            'terms',
            'bankAccounts',
            'documents',
            'performanceHistory',
            'purchases',
            'branch',
            'creator'
        ]);

        $creditUtilization = $supplier->getCreditUtilizationPercentage();
        $expiringDocs = $supplier->getExpiringDocuments();
        $latestPerformance = $supplier->getLatestPerformance();

        return view('suppliers.show', compact(
            'supplier',
            'creditUtilization',
            'expiringDocs',
            'latestPerformance'
        ));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier): View
    {
        $branches = Branch::active()->get();
        $statuses = ['active', 'inactive', 'blacklisted'];
        $creditRatings = ['excellent', 'good', 'fair', 'poor'];

        return view('suppliers.edit', compact('supplier', 'branches', 'statuses', 'creditRatings'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'code' => "required|string|unique:suppliers,code,{$supplier->id}",
            'name' => 'required|string|max:255',
            'email' => "nullable|email|unique:suppliers,email,{$supplier->id}",
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,blacklisted',
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Search suppliers (AJAX).
     */
    public function search(Request $request)
    {
        $term = $request->get('q', '');

        $suppliers = Supplier::search($term)
            ->active()
            ->select('id', 'code', 'name', 'email', 'status')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    /**
     * Get supplier details (AJAX).
     */
    public function getDetails(Supplier $supplier)
    {
        return response()->json([
            'id' => $supplier->id,
            'code' => $supplier->code,
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'credit_limit' => $supplier->credit_limit,
            'outstanding_balance' => $supplier->outstanding_balance,
            'available_credit' => $supplier->getAvailableCredit(),
            'credit_utilization' => $supplier->getCreditUtilizationPercentage(),
            'payment_terms_days' => $supplier->payment_terms_days,
            'status' => $supplier->status,
            'is_verified' => $supplier->is_verified,
            'primary_bank_account' => $supplier->primaryBankAccount?->first()?->only(['id', 'bank_name', 'account_number']),
            'primary_contact' => $supplier->primaryContact?->first()?->only(['id', 'name', 'email', 'phone']),
        ]);
    }

    /**
     * Check if supplier can accept purchase.
     */
    public function checkCredit(Request $request, Supplier $supplier)
    {
        $amount = $request->get('amount', 0);

        return response()->json([
            'can_accept' => $supplier->canAcceptPurchase($amount),
            'available_credit' => $supplier->getAvailableCredit(),
            'required_amount' => $amount,
            'message' => $supplier->canAcceptPurchase($amount)
                ? 'Supplier can accept this purchase.'
                : 'Purchase amount exceeds available credit limit.',
        ]);
    }

    /**
     * Get supplier performance summary.
     */
    public function performanceSummary(Supplier $supplier)
    {
        $latestPerformance = $supplier->getLatestPerformance();

        if (!$latestPerformance) {
            return response()->json(['message' => 'No performance records found'], 404);
        }

        return response()->json([
            'record_date' => $latestPerformance->record_date->format('Y-m-d'),
            'on_time_delivery_percentage' => $latestPerformance->on_time_delivery_percentage,
            'quality_rating' => $latestPerformance->quality_rating,
            'price_competitiveness' => $latestPerformance->price_competitiveness,
            'communication_rating' => $latestPerformance->communication_rating,
            'overall_score' => $latestPerformance->overall_score,
            'score_rating' => $latestPerformance->getScoreRating(),
        ]);
    }

    /**
     * Export suppliers list.
     */
    public function export(Request $request)
    {
        $query = Supplier::query();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->byBranch($request->branch_id);
        }

        $suppliers = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=suppliers_' . now()->format('Y-m-d') . '.csv',
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['Code', 'Name', 'Email', 'Phone', 'City', 'Status', 'Credit Limit', 'Outstanding Balance', 'Payment Terms', 'Quality Rating']);

            // Data rows
            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->code,
                    $supplier->name,
                    $supplier->email,
                    $supplier->phone,
                    $supplier->city,
                    $supplier->status,
                    $supplier->credit_limit,
                    $supplier->outstanding_balance,
                    $supplier->payment_terms_days,
                    $supplier->quality_rating,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get suppliers with outstanding balance.
     */
    public function withOutstanding()
    {
        $suppliers = Supplier::withOutstanding()
            ->select('id', 'code', 'name', 'outstanding_balance', 'credit_limit')
            ->with('branch')
            ->paginate(15);

        return view('suppliers.outstanding', compact('suppliers'));
    }

    /**
     * Get low credit suppliers.
     */
    public function lowCredit()
    {
        $suppliers = Supplier::active()
            ->with('branch')
            ->get()
            ->filter(function ($supplier) {
                return $supplier->getCreditUtilizationPercentage() > 75;
            })
            ->paginate(15);

        return view('suppliers.low-credit', compact('suppliers'));
    }
}

<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\DebitNote;
use App\Models\Purchase;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class DebitNotes extends Component
{
    use Interactions, WithPagination, ExportsCsv;

    public bool $showCreateModal = false;
    public bool $showDetailModal = false;

    // Form fields
    public ?string $supplier_id = null;
    public ?string $purchase_id = null;
    public string $debit_note_date = '';
    public string $reason = '';

    public array $lineItems = [];

    // Detail view
    public ?int $viewingId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->debit_note_date = now()->toDateString();
        $this->lineItems = [
            ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0],
        ];
    }

    public function openCreateModal(): void
    {
        $this->reset(['supplier_id', 'purchase_id', 'reason', 'lineItems']);
        $this->debit_note_date = now()->toDateString();
        $this->lineItems = [
            ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0],
        ];
        $this->showCreateModal = true;
    }

    public function addLine(): void
    {
        $this->lineItems[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0];
    }

    public function removeLine(int $index): void
    {
        array_splice($this->lineItems, $index, 1);
    }

    public function getLineTotal(array $item): float
    {
        return (float) $item['quantity'] * (float) $item['unit_price'];
    }

    public function getSubtotal(): float
    {
        return array_sum(array_map(fn($i) => $this->getLineTotal($i), $this->lineItems));
    }

    public function getTaxTotal(): float
    {
        return array_sum(array_map(fn($i) => $this->getLineTotal($i) * ((float) $i['tax_rate'] / 100), $this->lineItems));
    }

    public function save(): void
    {
        $this->validate([
            'supplier_id'      => 'required|exists:suppliers,id',
            'debit_note_date'  => 'required|date',
            'reason'           => 'required|string|max:500',
            'lineItems'        => 'required|array|min:1',
            'lineItems.*.description' => 'required|string',
            'lineItems.*.quantity'    => 'required|numeric|min:0.01',
            'lineItems.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $subtotal  = $this->getSubtotal();
        $taxAmount = $this->getTaxTotal();

        $debitNote = DebitNote::create([
            'branch_id'        => current_branch_id(),
            'supplier_id'      => $this->supplier_id,
            'purchase_id'      => $this->purchase_id ?: null,
            'debit_note_date'  => $this->debit_note_date,
            'reason'           => $this->reason,
            'subtotal'         => $subtotal,
            'tax_amount'       => $taxAmount,
            'total'            => $subtotal + $taxAmount,
            'status'           => 'draft',
            'amount_applied'   => 0,
            'created_by'       => auth()->id(),
            'gl_posting_status' => 'pending',
        ]);

        foreach ($this->lineItems as $item) {
            $lineTotal = $this->getLineTotal($item);
            $debitNote->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'tax_rate'    => $item['tax_rate'],
                'line_total'  => $lineTotal,
            ]);
        }

        $this->showCreateModal = false;
        $this->toast()->success('Debit note created.')->send();
    }

    public function issue(int $id): void
    {
        $note = DebitNote::where('branch_id', current_branch_id())->findOrFail($id);

        if ($note->status !== 'draft') {
            $this->toast()->error('Only draft debit notes can be issued.')->send();
            return;
        }

        $note->update(['status' => 'issued']);
        $this->toast()->success("Debit note {$note->debit_note_number} issued and posted to GL.")->send();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->showDetailModal = true;
    }

    public function updatedSupplierId(): void
    {
        $this->purchase_id = null;
    }

    public function exportToCsv()
    {
        $branchId = current_branch_id();

        $rows = DebitNote::where('branch_id', $branchId)
            ->when($this->search, fn($q) => $q->where('debit_note_number', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%"))
            ->with('supplier')
            ->orderByDesc('debit_note_date')
            ->get()
            ->map(fn ($n) => [
                $n->debit_note_number,
                $n->debit_note_date ? \Illuminate\Support\Carbon::parse($n->debit_note_date)->format('Y-m-d') : '',
                $n->supplier?->name ?? '',
                $n->reason ?? '',
                number_format((float) $n->subtotal, 2, '.', ''),
                number_format((float) $n->tax_amount, 2, '.', ''),
                number_format((float) $n->total, 2, '.', ''),
                number_format((float) $n->amount_applied, 2, '.', ''),
                ucfirst((string) $n->status),
            ]);

        return $this->streamCsv(
            $this->csvFilename('debit_notes'),
            ['Debit Note #', 'Date', 'Supplier', 'Reason', 'Subtotal', 'Tax', 'Total', 'Amount Applied', 'Status'],
            $rows
        );
    }

    public function render()
    {
        $branchId = current_branch_id();

        $notes = DebitNote::where('branch_id', $branchId)
            ->when($this->search, fn($q) => $q->where('debit_note_number', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%"))
            ->with(['supplier', 'items', 'creator'])
            ->orderByDesc('debit_note_date')
            ->paginate(15);

        $viewingNote = $this->viewingId
            ? DebitNote::with(['items', 'supplier'])->find($this->viewingId)
            : null;

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        $supplierPurchases = $this->supplier_id
            ? Purchase::where('branch_id', $branchId)
                ->where('supplier_id', $this->supplier_id)
                ->orderByDesc('purchase_date')
                ->limit(50)
                ->get(['id', 'purchase_number', 'purchase_date', 'total_cost'])
            : collect();

        return view('livewire.branch-dashboard.accounting.simple.debit-notes', [
            'notes'             => $notes,
            'suppliers'         => $suppliers,
            'supplierPurchases' => $supplierPurchases,
            'viewingNote'       => $viewingNote,
        ]);
    }
}

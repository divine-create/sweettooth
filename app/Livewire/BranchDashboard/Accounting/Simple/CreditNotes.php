<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\CreditNote;
use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class CreditNotes extends Component
{
    use Interactions, WithPagination, ExportsCsv;

    public bool $showCreateModal = false;
    public bool $showDetailModal = false;

    // Form fields
    public ?string $sale_id = null;
    public string $credit_note_date = '';
    public string $reason = '';

    // Line items
    public array $lineItems = [];

    // Detail view
    public ?int $viewingId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->credit_note_date = now()->toDateString();
        $this->lineItems = [
            ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0],
        ];
    }

    public function openCreateModal(): void
    {
        $this->reset(['sale_id', 'reason', 'lineItems']);
        $this->credit_note_date = now()->toDateString();
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
            'credit_note_date' => 'required|date',
            'reason'           => 'required|string|max:500',
            'lineItems'        => 'required|array|min:1',
            'lineItems.*.description' => 'required|string',
            'lineItems.*.quantity'    => 'required|numeric|min:0.01',
            'lineItems.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $subtotal = $this->getSubtotal();
        $taxAmount = $this->getTaxTotal();

        $creditNote = CreditNote::create([
            'branch_id'        => current_branch_id(),
            'sale_id'          => $this->sale_id ?: null,
            'credit_note_date' => $this->credit_note_date,
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
            $creditNote->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'tax_rate'    => $item['tax_rate'],
                'line_total'  => $lineTotal,
            ]);
        }

        $this->showCreateModal = false;
        $this->toast()->success('Credit note created.')->send();
    }

    public function issue(int $id): void
    {
        $note = CreditNote::where('branch_id', current_branch_id())->findOrFail($id);

        if ($note->status !== 'draft') {
            $this->toast()->error('Only draft credit notes can be issued.')->send();
            return;
        }

        $note->update(['status' => 'issued']);
        $this->toast()->success("Credit note {$note->credit_note_number} issued and posted to GL.")->send();
    }

    public function viewDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->showDetailModal = true;
    }

    public function exportToCsv()
    {
        $branchId = current_branch_id();

        $rows = CreditNote::where('branch_id', $branchId)
            ->when($this->search, fn($q) => $q->where('credit_note_number', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%"))
            ->with('customer')
            ->orderByDesc('credit_note_date')
            ->get()
            ->map(fn ($n) => [
                $n->credit_note_number,
                $n->credit_note_date ? \Illuminate\Support\Carbon::parse($n->credit_note_date)->format('Y-m-d') : '',
                $n->customer?->name ?? '',
                $n->reason ?? '',
                number_format((float) $n->subtotal, 2, '.', ''),
                number_format((float) $n->tax_amount, 2, '.', ''),
                number_format((float) $n->total, 2, '.', ''),
                number_format((float) $n->amount_applied, 2, '.', ''),
                ucfirst((string) $n->status),
            ]);

        return $this->streamCsv(
            $this->csvFilename('credit_notes'),
            ['Credit Note #', 'Date', 'Customer', 'Reason', 'Subtotal', 'Tax', 'Total', 'Amount Applied', 'Status'],
            $rows
        );
    }

    public function render()
    {
        $branchId = current_branch_id();

        $notes = CreditNote::where('branch_id', $branchId)
            ->when($this->search, fn($q) => $q->where('credit_note_number', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%"))
            ->with(['items', 'creator'])
            ->orderByDesc('credit_note_date')
            ->paginate(15);

        $viewingNote = $this->viewingId
            ? CreditNote::with('items')->find($this->viewingId)
            : null;

        $sales = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->orderByDesc('sale_time')
            ->limit(100)
            ->get(['id', 'sale_number', 'sale_time', 'total']);

        return view('livewire.branch-dashboard.accounting.simple.credit-notes', [
            'notes'       => $notes,
            'sales'       => $sales,
            'viewingNote' => $viewingNote,
        ]);
    }
}

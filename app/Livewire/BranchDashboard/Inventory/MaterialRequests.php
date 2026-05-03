<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\MaterialRequest;
use App\Services\MaterialRequestService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class MaterialRequests extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $statusFilter = 'all';

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showDetailModal = false;

    public $selectedRequest = null;

    public $requestDetails = [];

    public array $newItems = [];

    public string $newRequestDate = '';

    public string $newShift = 'morning';

    public string $newNotes = '';

    public ?int $targetDepartmentId = null;

    public function mount()
    {
        $this->b_id = request()->query('b_id');
        $this->newRequestDate = now()->format('Y-m-d');
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?? request()->query('b_id');
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = MaterialRequest::forBranch($branchId)
            ->with('department', 'details.item', 'details.unitOfMeasure')
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->withStatus($this->statusFilter);
        }

        if ($this->search) {
            $query->where('request_number', 'like', '%'.$this->search.'%');
        }

        $requests = $query->get();

        return view('livewire.branch-dashboard.inventory.material-requests', [
            'requests' => $requests,
        ]);
    }

    public function openCreateModal()
    {
        $this->newItems = [];
        $this->newRequestDate = now()->format('Y-m-d');
        $this->newShift = 'morning';
        $this->newNotes = '';
        $this->targetDepartmentId = null;
        $this->showCreateModal = true;
    }

    public function addItem()
    {
        $this->newItems[] = [
            'item_id' => '',
            'quantity' => '',
            'uom_id' => '',
            'notes' => '',
        ];
    }

    public function fillUom($index)
    {
        $itemId = $this->newItems[$index]['item_id'] ?? null;
        if ($itemId) {
            $item = Item::find($itemId);
            if ($item && $item->uom_id) {
                $this->newItems[$index]['uom_id'] = $item->uom_id;
            }
        }
    }

    public function removeItem($index)
    {
        unset($this->newItems[$index]);
        $this->newItems = array_values($this->newItems);
    }

    public function saveRequest()
    {
        $this->validate([
            'targetDepartmentId' => 'required|integer|exists:departments,id',
            'newRequestDate' => 'required|date',
            'newShift' => 'required|in:morning,afternoon',
            'newItems' => 'required|array|min:1',
            'newItems.*.item_id' => 'required|integer',
            'newItems.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $branchId = $this->getBranchId();
        $targetDept = Department::find($this->targetDepartmentId);
        $actor = current_actor();

        $branchCode = Branch::find($branchId)?->code ?? 'BR';
        $deptCode = $targetDept ? strtoupper(substr($targetDept->slug, 0, 3)) : 'DEPT';

        $items = array_map(function ($item) {
            return [
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'uom_id' => $item['uom_id'],
                'notes' => $item['notes'] ?? null,
            ];
        }, $this->newItems);

        $service = app(MaterialRequestService::class);
        $service->createRequest([
            'branch_id' => $branchId,
            'department_id' => $this->targetDepartmentId,
            'department_code' => $deptCode,
            'requested_by_id' => $actor?->id,
            'requested_by_type' => get_class($actor),
            'request_date' => $this->newRequestDate,
            'shift' => $this->newShift,
            'notes' => $this->newNotes,
            'items' => $items,
        ]);

        $this->toast()->success('Material request created successfully')->send();
        $this->showCreateModal = false;
    }

    public function viewDetail(MaterialRequest $request)
    {
        $this->selectedRequest = $request;
        $this->requestDetails = $request->details->map(function ($detail) {
            return [
                'id' => $detail->id,
                'item_name' => $detail->item?->name ?? 'Unknown',
                'quantity_requested' => $detail->quantity_requested,
                'quantity_approved' => $detail->quantity_approved,
                'quantity_dispatched' => $detail->quantity_dispatched,
                'uom_symbol' => $detail->unitOfMeasure?->symbol ?? 'N/A',
            ];
        })->toArray();
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showDetailModal = false;
        $this->selectedRequest = null;
        $this->requestDetails = [];
    }
}

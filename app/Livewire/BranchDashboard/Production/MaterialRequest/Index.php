<?php

namespace App\Livewire\BranchDashboard\Production\MaterialRequest;

use App\Models\Department;
use App\Models\Item;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestDetail;
use App\Models\Shift;
use App\Services\MaterialRequestService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?Department $department = null;

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

    public function mount($deptSlug)
    {
        $this->dept_slug = $deptSlug;
        $this->b_id = request()->query('b_id');
        $this->department = Department::where('slug', $deptSlug)->first();
        $this->newRequestDate = now()->format('Y-m-d');
        $this->newShift = $this->resolveCurrentShift();

        if (! $this->department) {
            abort(404, 'Department not found');
        }
    }

    private function resolveCurrentShift(): string
    {
        $activeShift = Shift::where('shift_date', today())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('employee_id', auth()->id())
                  ->orWhere('department_id', $this->department?->id);
            })
            ->latest('clock_in')
            ->value('shift_type');

        return in_array($activeShift, ['morning', 'afternoon', 'night']) ? $activeShift : 'morning';
    }

    public function getBranchId(): ?string
    {
        return $this->b_id 
            ?: (request()->query('b_id') 
            ?: (current_branch_id() 
            ?: (session('selected_branch_id') 
            ?: auth()->user()?->branch_id)));
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = MaterialRequest::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->forDepartment($this->department->id)
            ->with('details.item', 'details.unitOfMeasure')
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->withStatus($this->statusFilter);
        }

        if ($this->search) {
            $query->where('request_number', 'like', '%'.$this->search.'%');
        }

        $requests = $query->get();

        return view('livewire.branch-dashboard.production.material-request.index', [
            'requests' => $requests,
        ]);
    }

    public function openCreateModal()
    {
        $this->newItems = [];
        $this->newRequestDate = now()->format('Y-m-d');
        $this->newShift = $this->resolveCurrentShift();
        $this->newNotes = '';
        $this->addItem();
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

    public function updatedNewItems($value, $key = null)
    {
        // Livewire 3 passes ($value, $key) e.g. ($value, '0.item_id')
        if (is_string($key) && str_contains($key, 'item_id')) {
            $parts = explode('.', $key);
            $index = $parts[0];
            if (isset($this->newItems[$index]) && !empty($value)) {
                $item = Item::find($value);
                if ($item && $item->uom_id) {
                    $this->newItems[$index]['uom_id'] = $item->uom_id;
                }
            }
        } elseif (is_numeric($value) && isset($this->newItems[$value])) {
            // Fallback if legacy parameter order was ($index, $value)
            $item = Item::find($key);
            if ($item && $item->uom_id) {
                $this->newItems[$value]['uom_id'] = $item->uom_id;
            }
        }
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
        try {
            $this->validate([
                'newRequestDate' => 'required|date',
                'newShift' => 'required|in:morning,afternoon,night',
                'newItems' => 'required|array|min:1',
                'newItems.*.item_id' => 'required|integer|exists:items,id',
                'newItems.*.quantity' => 'required|numeric|min:0.01',
            ]);

            $branchId = $this->getBranchId();
            if (!$branchId) {
                throw new \Exception('Branch context is required to create a material request.');
            }

            $actor = current_actor() ?? auth()->user();

            $items = array_map(function ($item) {
                $itemModel = Item::find($item['item_id']);
                $uomId = !empty($item['uom_id']) ? (int) $item['uom_id'] : ($itemModel?->uom_id ?: null);

                return [
                    'item_id' => (int) $item['item_id'],
                    'quantity' => (float) $item['quantity'],
                    'uom_id' => $uomId,
                    'notes' => $item['notes'] ?? null,
                ];
            }, $this->newItems);

            $service = app(MaterialRequestService::class);
            $service->createRequest([
                'branch_id' => $branchId,
                'department_id' => $this->department->id,
                'department_code' => strtoupper(substr($this->department->slug, 0, 3)),
                'requested_by_id' => $actor?->id,
                'requested_by_type' => $actor ? get_class($actor) : null,
                'request_date' => $this->newRequestDate,
                'shift' => $this->newShift,
                'notes' => $this->newNotes,
                'items' => $items,
            ]);

            $this->toast()->success('Material request created successfully')->send();
            $this->showCreateModal = false;
            $this->newItems = [];
            $this->newNotes = '';
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
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
                'can_be_deleted' => $detail->canBeDeleted(),
            ];
        })->toArray();
        $this->showDetailModal = true;
    }

    public function deleteRequest(int $requestId)
    {
        try {
            $request = MaterialRequest::where('branch_id', $this->getBranchId())
                ->where('department_id', $this->department->id)
                ->findOrFail($requestId);

            $service = app(MaterialRequestService::class);
            $service->deleteRequest($request);

            if ($this->selectedRequest?->id === $requestId) {
                $this->closeModal();
            }

            $this->toast()->success('Material request deleted successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function deleteItem(int $detailId)
    {
        try {
            $detail = MaterialRequestDetail::whereHas('request', function ($query) {
                $query->where('branch_id', $this->getBranchId())
                      ->where('department_id', $this->department->id);
            })->findOrFail($detailId);

            $requestId = $detail->request_id;
            $service = app(MaterialRequestService::class);
            $service->deleteRequestItem($detail);

            $freshRequest = MaterialRequest::find($requestId);
            if ($freshRequest) {
                $this->viewDetail($freshRequest);
            } else {
                $this->closeModal();
            }

            $this->toast()->success('Item removed from material request')->send();
        } catch (\Exception $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showDetailModal = false;
        $this->selectedRequest = null;
        $this->requestDetails = [];
    }
}

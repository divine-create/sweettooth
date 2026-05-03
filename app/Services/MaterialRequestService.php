<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Item;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestDetail;
use App\Models\MaterialRequestDispatch;
use Illuminate\Support\Facades\DB;

class MaterialRequestService
{
    public function createRequest(array $data): MaterialRequest
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::find($data['branch_id']);
            $departmentCode = $data['department_code'] ?? 'DEPT';

            $request = MaterialRequest::create([
                'branch_id' => $data['branch_id'],
                'department_id' => $data['department_id'],
                'request_number' => MaterialRequest::generateRequestNumber(
                    $branch?->code ?? 'BR',
                    $departmentCode
                ),
                'requested_by_id' => $data['requested_by_id'],
                'requested_by_type' => $data['requested_by_type'],
                'request_date' => $data['request_date'],
                'shift' => $data['shift'] ?? 'morning',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                MaterialRequestDetail::create([
                    'request_id' => $request->id,
                    'item_id' => $item['item_id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_approved' => 0,
                    'quantity_dispatched' => 0,
                    'uom_id' => $item['uom_id'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $request;
        });
    }

    public function approveItem(MaterialRequestDetail $detail, float $quantity): void
    {
        $detail->update([
            'quantity_approved' => $quantity,
        ]);

        $this->updateRequestStatus($detail->request);
    }

    public function dispatch(array $dispatches): void
    {
        DB::transaction(function () use ($dispatches) {
            foreach ($dispatches as $dispatchData) {
                $detail = MaterialRequestDetail::find($dispatchData['detail_id']);

                MaterialRequestDispatch::create([
                    'request_id' => $detail->request_id,
                    'item_id' => $dispatchData['item_id'],
                    'dispatched_by_id' => $dispatchData['dispatched_by_id'],
                    'dispatched_by_type' => $dispatchData['dispatched_by_type'],
                    'quantity' => $dispatchData['quantity'],
                    'uom_id' => $dispatchData['uom_id'],
                    'dispatch_time' => now(),
                    'notes' => $dispatchData['notes'] ?? null,
                ]);

                $detail->quantity_dispatched += $dispatchData['quantity'];
                $detail->save();

                $productRequest = $detail->request;
                $this->updateRequestStatus($productRequest);
            }
        });
    }

    public function cancelRequest(MaterialRequest $request): void
    {
        if (!$request->isPending()) {
            throw new \InvalidArgumentException('Only pending requests can be cancelled');
        }

        $request->update(['status' => 'cancelled']);
    }

    protected function updateRequestStatus(MaterialRequest $request): void
    {
        $details = $request->details()->get();

        $allApproved = $details->every(fn ($d) => $d->quantity_approved > 0);
        $allDispatched = $details->every(fn ($d) => $d->isFullyDispatched());

        if ($allDispatched) {
            $request->update(['status' => 'completed']);
        } elseif ($allApproved) {
            $request->update(['status' => 'approved']);
        }
    }

    public function getRequests(MaterialRequest $request): array
    {
        return [
            'id' => $request->id,
            'request_number' => $request->request_number,
            'department' => $request->department?->name,
            'request_date' => $request->request_date,
            'shift' => $request->shift,
            'status' => $request->status,
            'items_count' => $request->details()->count(),
            'created_at' => $request->created_at,
        ];
    }
}
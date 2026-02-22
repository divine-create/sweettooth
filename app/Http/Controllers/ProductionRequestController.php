<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProductionRequest;
use App\Models\ProductionProgressFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductionRequestController extends Controller
{
    /**
     * Create a new production request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'production_department_id' => ['required', 'exists:departments,id'],
            'sales_department_id' => ['required', 'exists:departments,id'],
            'priority' => ['required', Rule::in(['normal', 'urgent'])],
            'planned_production_quantity' => ['required', 'numeric', 'min:0.01'],
            'requested_units' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'recipe_id' => ['nullable', 'exists:recipes,id'],
            'item_request_id' => ['nullable', 'exists:item_requests,id'],
        ]);

        $validated['created_by_id'] = auth()->id();
        $validated['status'] = 'pending';
        if (!isset($validated['requested_units'])) {
            $validated['requested_units'] = $validated['planned_production_quantity'];
        }

        $request = ProductionRequest::create($validated);

        return response()->json([
            'message' => 'Production request created successfully',
            'data' => $request->load(['productionDepartment', 'salesDepartment', 'createdBy'])
        ], 201);
    }

    /**
     * Get all production requests with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductionRequest::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by production department
        if ($request->has('production_department_id')) {
            $query->where('production_department_id', $request->production_department_id);
        }

        // Filter by sales department
        if ($request->has('sales_department_id')) {
            $query->where('sales_department_id', $request->sales_department_id);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by created_by (user's own requests)
        if ($request->has('created_by_id')) {
            $query->where('created_by_id', $request->created_by_id);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $query->orderBy($sortBy, $sortDirection);

        $requests = $query->paginate($request->get('per_page', 20));

        return response()->json($requests);
    }

    /**
     * Get a specific production request
     */
    public function show(ProductionRequest $productionRequest): JsonResponse
    {
        return response()->json(
            $productionRequest->load([
                'productionDepartment',
                'salesDepartment',
                'createdBy',
                'progressFeedback',
                'dispatches'
            ])
        );
    }

    /**
     * Update a production request
     */
    public function update(Request $request, ProductionRequest $productionRequest): JsonResponse
    {
        $validated = $request->validate([
            'priority' => ['sometimes', Rule::in(['normal', 'urgent'])],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'quality_check', 'completed', 'dispatched', 'accepted', 'rejected', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'completed_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ]);

        $productionRequest->update($validated);

        return response()->json([
            'message' => 'Production request updated successfully',
            'data' => $productionRequest->load(['productionDepartment', 'salesDepartment', 'createdBy'])
        ]);
    }

    /**
     * Cancel a production request
     */
    public function cancel(ProductionRequest $productionRequest): JsonResponse
    {
        if (!$productionRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'This request cannot be cancelled'
            ], 422);
        }

        $productionRequest->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Production request cancelled successfully',
            'data' => $productionRequest
        ]);
    }

    /**
     * Get progress updates for a request
     */
    public function getProgress(ProductionRequest $productionRequest): JsonResponse
    {
        $progress = $productionRequest->progressFeedback()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'request_id' => $productionRequest->id,
            'progress' => $progress
        ]);
    }

    /**
     * Get progress milestones for a request
     */
    public function getMilestones(ProductionRequest $productionRequest): JsonResponse
    {
        $milestones = $productionRequest->progressFeedback()
            ->select('milestone', 'progress_percentage', 'created_at', 'notes', 'updated_by_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'request_id' => $productionRequest->id,
            'milestones' => $milestones
        ]);
    }

    /**
     * Delete a production request
     */
    public function destroy(ProductionRequest $productionRequest): JsonResponse
    {
        if ($productionRequest->dispatches()->exists()) {
            return response()->json([
                'message' => 'Cannot delete request with existing dispatches'
            ], 422);
        }

        $productionRequest->delete();

        return response()->json([
            'message' => 'Production request deleted successfully'
        ]);
    }
}

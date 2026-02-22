<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

if (! function_exists('branch_route')) {
    /**
     * Generate a route URL that automatically includes and validates the current b_id query parameter.
     * 
     * Priority:
     * 1. Manually passed params['b_id']
     * 2. Request query parameter (?b_id=xxx)
     * 3. Session value (for super admins)
     * 4. current_branch_id() helper
     * 
     * IMPORTANT:
     * - Super admins: b_id can be passed to switch branches
     * - Employees: b_id MUST match their assigned branch (or will be rejected by middleware)
     */
    function branch_route(string $name, array $params = [], bool $absolute = true): string
    {
        // Try to get the current b_id from multiple sources
        $b_id = $params['b_id'] ?? Request::query('b_id') ?? current_branch_id();

        // Validate that it's a proper UUID and not empty
        $validator = Validator::make(['b_id' => $b_id], [
            'b_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            Log::warning('Invalid b_id in branch_route - route will lack branch context', [
                'input' => $b_id,
                'route' => $name,
                'user_type' => is_super_admin() ? 'super_admin' : 'employee',
            ]);
            // Remove invalid b_id - the route will be generated without it
            // Note: This may cause middleware to reject the request
            unset($params['b_id']);
        } else {
            $params['b_id'] = $b_id;
        }

        return route($name, $params, $absolute);
    }
}

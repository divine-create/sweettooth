<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

if (! function_exists('department_route')) {
    /**
     * Generate a route URL that includes department context but excludes branch ID.
     *
     * This function creates department-specific routes without the b_id parameter,
     * allowing access to department functionality without requiring a specific branch context.
     *
     * @param string $name The route name
     * @param array $params Additional route parameters (department slug, etc.)
     * @param bool $absolute Whether to generate an absolute URL
     * @return string The generated route URL
     */
    function department_route(string $name, array $params = [], bool $absolute = true): string
    {
        // Remove any b_id parameter that might have been passed
        unset($params['b_id']);
        
        // Ensure department slug is properly handled
        if (isset($params['deptSlug']) && empty($params['deptSlug'])) {
            unset($params['deptSlug']);
        }
        
        if (isset($params['salesDeptSlug']) && empty($params['salesDeptSlug'])) {
            unset($params['salesDeptSlug']);
        }
        
        return route($name, $params, $absolute);
    }
}

if (! function_exists('branchless_department_route')) {
    /**
     * Alternative name for department_route function for clarity
     *
     * @param string $name The route name
     * @param array $params Additional route parameters
     * @param bool $absolute Whether to generate an absolute URL
     * @return string The generated route URL without branch context
     */
    function branchless_department_route(string $name, array $params = [], bool $absolute = true): string
    {
        return department_route($name, $params, $absolute);
    }
}
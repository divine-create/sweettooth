<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestDepartmentRouteController extends Controller
{
    public function testDepartmentRoute()
    {
        // Test the new department_route helper function
        $routeWithBranch = route('branch-dashboard.branch.departments.index', ['b_id' => 'test-branch-id']);
        $routeWithoutBranch = department_route('branch-dashboard.branch.departments.index');
        
        // Example with department slug
        $prodRoute = department_route('branch-dashboard.production.products', ['deptSlug' => 'production-dept']);
        $salesRoute = department_route('branch-dashboard.sales-dashboard.pos.index', ['salesDeptSlug' => 'sales-dept']);
        
        return response()->json([
            'route_with_branch' => $routeWithBranch,
            'route_without_branch' => $routeWithoutBranch,
            'production_route' => $prodRoute,
            'sales_route' => $salesRoute,
            'helper_exists' => function_exists('department_route')
        ]);
    }
}
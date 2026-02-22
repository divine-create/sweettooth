<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete DepartmentPage records that reference the Kitchen Module
        \DB::table('department_pages')
          ->where('route_name', 'branch-dashboard.production.module.index')
          ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback since we're removing deprecated functionality
    }
};

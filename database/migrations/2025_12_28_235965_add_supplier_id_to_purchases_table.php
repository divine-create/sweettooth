<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add supplier_id foreign key to purchases table.
     * Links purchases to the suppliers management system.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable()->after('branch_id')->constrained('suppliers')->onDelete('restrict');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'supplier_id')) {
                    $table->dropForeign(['supplier_id']);
                    $table->dropColumn('supplier_id');
                }
            });
        }
    }
};

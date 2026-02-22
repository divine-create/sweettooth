<?php

use App\Enums\ProductionRequestSourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('production_requests', 'source_type')) {
                $table->enum('source_type', ProductionRequestSourceType::values())
                    ->default(ProductionRequestSourceType::MANUAL->value)
                    ->after('status');
                $table->index('source_type');
            }

            if (!Schema::hasColumn('production_requests', 'source_id')) {
                $table->string('source_id', 64)->nullable()->after('source_type');
                $table->index(['source_type', 'source_id'], 'production_requests_source_lookup_idx');
            }
        });

        DB::table('production_requests')
            ->whereNotNull('sales_department_id')
            ->whereNull('item_request_id')
            ->update([
                'source_type' => ProductionRequestSourceType::SALES_DEMAND->value,
            ]);

        DB::table('production_requests')
            ->whereNotNull('item_request_id')
            ->update([
                'source_type' => ProductionRequestSourceType::RESTOCK->value,
            ]);
    }

    public function down(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            if (Schema::hasColumn('production_requests', 'source_id')) {
                $table->dropIndex('production_requests_source_lookup_idx');
                $table->dropColumn('source_id');
            }

            if (Schema::hasColumn('production_requests', 'source_type')) {
                $table->dropIndex(['source_type']);
                $table->dropColumn('source_type');
            }
        });
    }
};

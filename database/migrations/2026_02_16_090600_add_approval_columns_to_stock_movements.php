<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'approved_by_id')) {
                $table->uuid('approved_by_id')->nullable()->after('gl_posted_at');
                $table->string('approved_by_type')->nullable()->after('approved_by_id');
                $table->timestamp('approved_at')->nullable()->after('approved_by_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'approved_at')) {
                $table->dropColumn(['approved_by_id', 'approved_by_type', 'approved_at']);
            }
        });
    }
};

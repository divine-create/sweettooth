<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                $table->decimal('quantity_reserved', 12, 2)->default(0)->after('quantity_sold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_stocks', 'quantity_reserved')) {
                $table->dropColumn('quantity_reserved');
            }
        });
    }
};

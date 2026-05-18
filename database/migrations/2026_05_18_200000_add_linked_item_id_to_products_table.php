<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('linked_item_id')->nullable()->after('branch_id');
            $table->foreign('linked_item_id')->references('id')->on('items')->onDelete('set null');
            $table->index('linked_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['linked_item_id']);
            $table->dropIndex(['linked_item_id']);
            $table->dropColumn('linked_item_id');
        });
    }
};

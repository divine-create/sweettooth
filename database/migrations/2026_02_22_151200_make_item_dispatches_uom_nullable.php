<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_dispatches', function (Blueprint $table) {
            $table->string('uom')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting to enum requires manual specification of enum values.
        // Leave as-is to avoid destructive changes.
    }
};

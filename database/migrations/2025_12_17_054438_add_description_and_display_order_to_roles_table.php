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
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'description')) {
                $table->string('description')->nullable()->after('guard_name');
            }
            if (! Schema::hasColumn('roles', 'display_order')) {
                $table->integer('display_order')->default(0)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('roles', 'description')) {
                $columnsToDrop[] = 'description';
            }
            if (Schema::hasColumn('roles', 'display_order')) {
                $columnsToDrop[] = 'display_order';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

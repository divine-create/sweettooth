<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_remittances', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_remittances', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                $table->index('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_remittances', function (Blueprint $table) {
            if (Schema::hasColumn('pos_remittances', 'department_id')) {
                $table->dropForeignKeyIfExists(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};

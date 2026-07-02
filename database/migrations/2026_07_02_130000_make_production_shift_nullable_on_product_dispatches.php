<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ProductDispatch is not always tied to an active production shift: dispatches
     * from the Finished Goods sheet (goods produced earlier / in a closed shift) and
     * super-admin quick-produce dispatches legitimately have no shift. The code already
     * writes `production_shift_id => $shift?->id` / `shift_type => $shift?->shift_type`
     * (both null in those cases), but the columns were NOT NULL, causing a 500 on send.
     * sales_shift_id is already nullable — align these two.
     */
    public function up(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->unsignedBigInteger('production_shift_id')->nullable()->comment('Kitchen/Production shift')->change();
            $table->enum('shift_type', ['morning', 'afternoon', 'night'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->unsignedBigInteger('production_shift_id')->nullable(false)->comment('Kitchen/Production shift')->change();
            $table->enum('shift_type', ['morning', 'afternoon', 'night'])->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_requests', function (Blueprint $table) {
            $table->id();

            // Foreign relations
            $table->uuid('branch_id');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->unsignedBigInteger('department_id');
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');

            // Actor Pattern – Who created/requested the item request
            // Can be User (e.g. frontend staff) OR Employee (internal staff)
            $table->uuidMorphs('requested_by');           // created_by_id + created_by_type

            // Optional actors
            $table->uuidMorphs('approved_by');   // approved_by_id + approved_by_type
            $table->uuidMorphs('cancelled_by');
            $table->uuidMorphs('dispatched_by');

            // Timestamps for actions
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();

            // Request details
            $table->string('request_number')->unique();
            $table->date('request_date');
            $table->enum('shift', ['morning', 'afternoon'])->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'partially_dispatched',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_requests');
    }
};

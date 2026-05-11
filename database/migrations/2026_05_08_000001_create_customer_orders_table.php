<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->string('order_number')->unique();
            $table->enum('status', [
                'pending',
                'approved',
                'in_production',
                'completed',
                'rejected',
                'cancelled',
            ])->default('pending');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('branch_id', 'co_branch_fk')
                ->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('approved_by', 'co_approved_by_fk')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by', 'co_rejected_by_fk')
                ->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'status'], 'co_branch_status_idx');
            $table->index(['branch_id', 'created_at'], 'co_branch_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};

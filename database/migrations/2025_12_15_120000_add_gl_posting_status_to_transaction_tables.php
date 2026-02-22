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
        // Add to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->unsignedBigInteger('gl_entry_id')->nullable();
                $table->foreign('gl_entry_id')->references('id')->on('gl_entries')->onDelete('set null');
            }
        });

        // Add to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->unsignedBigInteger('gl_entry_id')->nullable();
                $table->foreign('gl_entry_id')->references('id')->on('gl_entries')->onDelete('set null');
            }
        });

        // Add to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->unsignedBigInteger('gl_entry_id')->nullable();
                $table->foreign('gl_entry_id')->references('id')->on('gl_entries')->onDelete('set null');
            }
        });

        // Add to stock_movements table
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])->default('pending');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->unsignedBigInteger('gl_entry_id')->nullable();
                $table->foreign('gl_entry_id')->references('id')->on('gl_entries')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop from sales
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'gl_entry_id')) {
                $table->dropForeign(['gl_entry_id']);
                $table->dropColumn(['gl_entry_id', 'gl_posted_at', 'gl_posting_error', 'gl_posting_status']);
            }
        });

        // Drop from purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'gl_entry_id')) {
                $table->dropForeign(['gl_entry_id']);
                $table->dropColumn(['gl_entry_id', 'gl_posted_at', 'gl_posting_error', 'gl_posting_status']);
            }
        });

        // Drop from payments
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'gl_entry_id')) {
                $table->dropForeign(['gl_entry_id']);
                $table->dropColumn(['gl_entry_id', 'gl_posted_at', 'gl_posting_error', 'gl_posting_status']);
            }
        });

        // Drop from stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'gl_entry_id')) {
                $table->dropForeign(['gl_entry_id']);
                $table->dropColumn(['gl_entry_id', 'gl_posted_at', 'gl_posting_error', 'gl_posting_status']);
            }
        });
    }
};

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
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])
                    ->default('pending')
                    ->after('total')
                    ->comment('GL posting status for accounting integration');
            }

            if (! Schema::hasColumn('sales', 'gl_posted_at')) {
                $table->timestamp('gl_posted_at')
                    ->nullable()
                    ->after('gl_posting_status')
                    ->comment('When the GL entries were posted');
            }

            if (! Schema::hasColumn('sales', 'gl_posting_error')) {
                $table->text('gl_posting_error')
                    ->nullable()
                    ->after('gl_posted_at')
                    ->comment('Error message if GL posting failed');
            }

            if (! Schema::hasColumn('sales', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')
                    ->nullable()
                    ->after('total')
                    ->comment('Link to bank account for cash sales');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'gl_posting_status')) {
                $table->enum('gl_posting_status', ['pending', 'posted', 'failed'])
                    ->default('pending')
                    ->after('amount')
                    ->comment('GL posting status for accounting integration');
            }

            if (! Schema::hasColumn('payments', 'gl_posted_at')) {
                $table->timestamp('gl_posted_at')
                    ->nullable()
                    ->after('gl_posting_status')
                    ->comment('When the GL entries were posted');
            }

            if (! Schema::hasColumn('payments', 'gl_posting_error')) {
                $table->text('gl_posting_error')
                    ->nullable()
                    ->after('gl_posted_at')
                    ->comment('Error message if GL posting failed');
            }

            if (! Schema::hasColumn('payments', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')
                    ->nullable()
                    ->after('amount')
                    ->comment('Link to bank account for payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = ['gl_posting_status', 'gl_posted_at', 'gl_posting_error', 'bank_account_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $columns = ['gl_posting_status', 'gl_posted_at', 'gl_posting_error', 'bank_account_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

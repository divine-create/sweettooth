<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->string('gl_posting_status')->default('pending')->after('notes');
            $table->timestamp('gl_posted_at')->nullable()->after('gl_posting_status');
            $table->text('gl_posting_error')->nullable()->after('gl_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropColumn(['gl_posting_status', 'gl_posted_at', 'gl_posting_error']);
        });
    }
};

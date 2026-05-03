<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE department_reports MODIFY COLUMN status ENUM('draft', 'pending_review', 'rejected', 'reviewed', 'compiled', 'sent_to_md') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE department_reports MODIFY COLUMN status ENUM('draft', 'pending_review', 'reviewed', 'compiled', 'sent_to_md') DEFAULT 'draft'");
    }
};

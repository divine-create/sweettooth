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
        // The supplier bank account form offers "business" as an account type,
        // but the original enum only allowed "investment". Add "business" (and
        // keep "investment" so any existing rows remain valid).
        DB::statement("ALTER TABLE supplier_bank_accounts MODIFY COLUMN account_type ENUM('checking', 'savings', 'business', 'investment') DEFAULT 'checking'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE supplier_bank_accounts MODIFY COLUMN account_type ENUM('checking', 'savings', 'investment') DEFAULT 'checking'");
    }
};

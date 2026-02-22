<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $users = User::whereNull('employee_number')->orderBy('id')->get();
        $counter = 1;

        foreach ($users as $user) {
            $user->employee_number = 'EMP' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $user->save();
            $counter++;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number', 50)->nullable()->change();
        });
    }
};

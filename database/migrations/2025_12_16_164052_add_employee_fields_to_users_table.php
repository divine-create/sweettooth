<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds all remaining employee-specific columns to users table
     * to complete the consolidation from dual authentication system.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal information
            if (! Schema::hasColumn('users', 'employee_number')) {
                $table->string('employee_number', 50)->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('address');
            }

            if (! Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('gender');
            }

            // Emergency contact
            if (! Schema::hasColumn('users', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('nationality');
            }

            if (! Schema::hasColumn('users', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            }

            // Employment details
            if (! Schema::hasColumn('users', 'termination_date')) {
                $table->date('termination_date')->nullable()->after('employment_status');
            }

            if (! Schema::hasColumn('users', 'probation_end_date')) {
                $table->date('probation_end_date')->nullable()->after('termination_date');
            }

            if (! Schema::hasColumn('users', 'shift_preference')) {
                $table->enum('shift_preference', ['morning', 'afternoon', 'night', 'rotating', 'flexible'])->nullable()->after('probation_end_date');
            }

            // Compensation
            if (! Schema::hasColumn('users', 'salary')) {
                $table->decimal('salary', 10, 2)->nullable()->after('shift_preference');
            }

            if (! Schema::hasColumn('users', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('salary');
            }

            if (! Schema::hasColumn('users', 'tax_id')) {
                $table->string('tax_id', 50)->nullable()->after('hourly_rate');
            }

            if (! Schema::hasColumn('users', 'bank_account')) {
                $table->string('bank_account', 100)->nullable()->after('tax_id');
            }

            // Additional info
            if (! Schema::hasColumn('users', 'allergies')) {
                $table->text('allergies')->nullable()->after('bank_account');
            }

            if (! Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('allergies');
            }

            // Performance tracking
            if (! Schema::hasColumn('users', 'last_performance_review_date')) {
                $table->date('last_performance_review_date')->nullable()->after('profile_photo');
            }

            if (! Schema::hasColumn('users', 'performance_rating')) {
                $table->decimal('performance_rating', 3, 1)->nullable()->after('last_performance_review_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropColumn([
                'employee_number',
                'address',
                'date_of_birth',
                'gender',
                'nationality',
                'emergency_contact_name',
                'emergency_contact_phone',
                'termination_date',
                'probation_end_date',
                'shift_preference',
                'salary',
                'hourly_rate',
                'tax_id',
                'bank_account',
                'allergies',
                'profile_photo',
                'last_performance_review_date',
                'performance_rating',
            ]);
        });
    }
};

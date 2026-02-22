<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add fields to production_requests table for sales-to-production workflow
        Schema::table('production_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('production_requests', 'sales_department_id')) {
                $table->foreignId('sales_department_id')->nullable()->after('shift_id')->constrained('departments')->onDelete('set null');
            }
            if (!Schema::hasColumn('production_requests', 'production_department_id')) {
                $table->foreignId('production_department_id')->nullable()->after('sales_department_id')->constrained('departments')->onDelete('set null');
            }
            if (!Schema::hasColumn('production_requests', 'status')) {
                $table->enum('status', ['pending', 'in_progress', 'quality_check', 'completed', 'dispatched', 'accepted', 'rejected', 'cancelled'])
                    ->default('pending')->after('planned_production_quantity');
            }
            if (!Schema::hasColumn('production_requests', 'priority')) {
                $table->enum('priority', ['normal', 'urgent'])->default('normal')->after('status');
            }
            if (!Schema::hasColumn('production_requests', 'created_by_id')) {
                $table->uuid('created_by_id')->nullable()->after('priority')->constrained('users')->references('id')->onDelete('set null');
            }
            if (!Schema::hasColumn('production_requests', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('created_by_id');
            }
            if (!Schema::hasColumn('production_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }

            // Add indexes for performance (only if they don't exist)
            if (!$this->indexExists('production_requests', 'production_requests_production_department_id_index')) {
                $table->index('production_department_id');
            }
            if (!$this->indexExists('production_requests', 'production_requests_sales_department_id_index')) {
                $table->index('sales_department_id');
            }
            if (!$this->indexExists('production_requests', 'production_requests_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('production_requests', 'production_requests_production_department_id_status_index')) {
                $table->index(['production_department_id', 'status']);
            }
        });

        // Create production progress feedback table
        if (!Schema::hasTable('production_progress_feedback')) {
            Schema::create('production_progress_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_request_id')->constrained('production_requests')->onDelete('cascade');
                $table->enum('milestone', ['started', 'in_production', 'quality_check', 'completed'])->default('in_production');
                $table->integer('progress_percentage')->default(0);
                $table->text('notes')->nullable();
                $table->uuid('updated_by_id')->nullable()->constrained('users')->references('id')->onDelete('set null');
                $table->timestamps();

                $table->index(['production_request_id', 'created_at'], 'idx_feedback_request_created');
                $table->index('milestone', 'idx_feedback_milestone');
            });
        }

        // Add FK to product_dispatches linking to production request
        if (Schema::hasTable('product_dispatches') && !Schema::hasColumn('product_dispatches', 'production_request_id')) {
            Schema::table('product_dispatches', function (Blueprint $table) {
                $table->foreignId('production_request_id')->nullable()->after('id')->constrained('production_requests')->onDelete('cascade');
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->pluck('name')
                ->contains($indexName);
        }

        return collect(DB::select("SHOW INDEXES FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('product_dispatches', 'production_request_id')) {
                $table->dropForeignIdFor('ProductionRequest');
                $table->dropColumn('production_request_id');
            }
        });

        Schema::dropIfExists('production_progress_feedback');

        Schema::table('production_requests', function (Blueprint $table) {
            if (Schema::hasColumn('production_requests', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
            if (Schema::hasColumn('production_requests', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('production_requests', 'created_by_id')) {
                $table->dropForeignIdFor('User', 'created_by_id');
                $table->dropColumn('created_by_id');
            }
            if (Schema::hasColumn('production_requests', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('production_requests', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('production_requests', 'production_department_id')) {
                $table->dropForeignIdFor('Department', 'production_department_id');
                $table->dropColumn('production_department_id');
            }
            if (Schema::hasColumn('production_requests', 'sales_department_id')) {
                $table->dropForeignIdFor('Department', 'sales_department_id');
                $table->dropColumn('sales_department_id');
            }
        });
    }
};

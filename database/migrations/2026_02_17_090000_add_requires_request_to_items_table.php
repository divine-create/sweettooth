<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasIndex = $this->indexExists('items', 'items_requires_request_index');

        Schema::table('items', function (Blueprint $table) use ($hasIndex) {
            if (! Schema::hasColumn('items', 'requires_request')) {
                $table->boolean('requires_request')->default(false)->after('status');
                if (! $hasIndex) {
                    $table->index('requires_request');
                }
            }
        });
    }

    public function down(): void
    {
        $hasIndex = $this->indexExists('items', 'items_requires_request_index');

        Schema::table('items', function (Blueprint $table) use ($hasIndex) {
            if (Schema::hasColumn('items', 'requires_request')) {
                if ($hasIndex) {
                    $table->dropIndex(['requires_request']);
                }
                $table->dropColumn('requires_request');
            }
        });
    }

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
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasIndex = $this->indexExists('item_request_details', 'item_request_details_requires_request_index');

        Schema::table('item_request_details', function (Blueprint $table) use ($hasIndex) {
            if (! Schema::hasColumn('item_request_details', 'requires_request')) {
                $table->boolean('requires_request')->default(false)->after('item_id');
                if (! $hasIndex) {
                    $table->index('requires_request');
                }
            }
        });

        $rows = DB::table('item_request_details as ird')
            ->leftJoin('items as i', 'i.id', '=', 'ird.item_id')
            ->select('ird.id', 'i.requires_request')
            ->get();

        foreach ($rows as $row) {
            DB::table('item_request_details')
                ->where('id', $row->id)
                ->update([
                    'requires_request' => (bool) ($row->requires_request ?? false),
                ]);
        }
    }

    public function down(): void
    {
        $hasIndex = $this->indexExists('item_request_details', 'item_request_details_requires_request_index');

        Schema::table('item_request_details', function (Blueprint $table) use ($hasIndex) {
            if (Schema::hasColumn('item_request_details', 'requires_request')) {
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

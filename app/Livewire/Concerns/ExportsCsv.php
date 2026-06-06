<?php

namespace App\Livewire\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared CSV export helper for Livewire components.
 *
 * Streams a CSV download using fputcsv so values are quoted/escaped correctly.
 * Rows may be arrays (in header order) or associative arrays whose keys match
 * the header. Pass headers explicitly, or omit them to derive from the first
 * associative row.
 */
trait ExportsCsv
{
    /**
     * @param  string  $filename  Download filename (a .csv extension is added if missing).
     * @param  array<int, string>  $header  Column headings.
     * @param  iterable<array<int|string, mixed>>  $rows  Row values.
     */
    protected function streamCsv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        if (! str_ends_with(strtolower($filename), '.csv')) {
            $filename .= '.csv';
        }

        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');

            if (! empty($header)) {
                fputcsv($handle, $header);
            }

            foreach ($rows as $row) {
                fputcsv($handle, is_array($row) ? array_values($row) : [$row]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Build a timestamped filename, e.g. csvFilename('transactions') => "transactions_2026-06-03_142530.csv".
     */
    protected function csvFilename(string $prefix): string
    {
        return $prefix.'_'.now()->format('Y-m-d_His').'.csv';
    }
}

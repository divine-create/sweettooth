<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use App\Support\CsvFromHtml;
use App\Jobs\ExportCsvJob;

trait Exportable
{
    /**
     * Universal export method for CSV exports
     *
     * @param string $filename          Base filename (without extension)
     * @param Collection|array $data    Data to export
     * @param string|array $views       Blade view path(s)
     * @param bool $queue               Set true for large datasets (>500 rows)
     * @param array $exportOptions      Configure: format, styles, etc.
     * @return mixed
     */
    public function export(
        string $filename,
        $data,
        $views,
        $formatOrQueue = null,
        bool $queue = false,
        array $exportOptions = []
    ) {
        $data = collect($data);

        if ($data->isEmpty()) {
            session()->flash('error', 'No data to export.');
            return redirect()->back();
        }

        // Backward compatibility: export($filename, $data, $views, 'excel', true)
        // We ignore format and treat 4th param as queue if it's boolean.
        if (is_bool($formatOrQueue)) {
            $queue = $formatOrQueue;
        }

        // Normalize views to array format
        $viewsArray = $this->normalizeViews($views);

        // Merge default options with custom options
        $options = array_merge($this->getDefaultExportOptions(), $exportOptions);

        // Determine if we should queue
        $shouldQueue = $queue && ($data->count() > ($options['queue_threshold'] ?? 500));

        if ($shouldQueue) {
            return $this->queueExports($filename, $data, $viewsArray, $options);
        }

        return $this->performImmediateExport($filename, $data, $viewsArray, $options);
    }

    /**
     * Normalize view paths to standardized array format
     */
    private function normalizeViews($views): array
    {
        if (is_string($views)) {
            return [
                'csv' => $views,
            ];
        }

        if (is_array($views)) {
            return [
                'csv' => $views['csv'] ?? $views['excel'] ?? $views[0] ?? null,
            ];
        }

        throw new \InvalidArgumentException('Views must be string or array');
    }

    /**
     * Get default export configuration options
     */
    private function getDefaultExportOptions(): array
    {
        return [
            // General Options
            'queue_threshold' => 500,
            'use_memory_limit' => true,
            'memory_limit' => '512M',
            'include_timestamp' => false,
            'timezone' => config('app.timezone', 'UTC'),
        ];
    }

    /**
     * Queue export jobs for large datasets
     */
    private function queueExports(
        string $filename,
        Collection $data,
        array $views,
        array $options
    ) {
        if ($views['csv']) {
            ExportCsvJob::dispatch(
                auth()->user()?->id,
                $filename,
                $data->toArray(),
                $views['csv'],
                $options
            );
        }

        session()->flash('success', 'Export queued. Your CSV will be generated in the background.');
        return redirect()->back();
    }

    /**
     * Perform immediate export for small datasets
     */
    private function performImmediateExport(
        string $filename,
        Collection $data,
        array $views,
        array $options
    ) {
        // Prepare filename with optional timestamp
        $finalFilename = $this->formatFilename($filename, $options['include_timestamp'] ?? false);

        if ($views['csv']) {
            return $this->generateCsv($finalFilename, $data, $views['csv'], $options);
        }

        session()->flash('error', 'No valid export view configured.');
        return redirect()->back();
    }

    /**
     * Generate CSV export
     */
    private function generateCsv(
        string $filename,
        Collection $data,
        string $view,
        array $options
    ) {
        try {
            // Validate view exists
            if (!view()->exists($view)) {
                throw new \InvalidArgumentException("Export view '{$view}' not found.");
            }

            $html = view($view, [
                'data' => $data,
                'forCsv' => true,
                'options' => $options,
            ])->render();

            $rows = CsvFromHtml::rows($html);

            return response()->streamDownload(function () use ($rows) {
                $output = fopen('php://output', 'w');
                CsvFromHtml::writeTo($output, $rows);
                fclose($output);
            }, $filename . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            \Log::error('CSV export failed: ' . $e->getMessage(), [
                'filename' => $filename,
                'view' => $view,
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'CSV export failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Format filename with optional timestamp
     */
    private function formatFilename(string $filename, bool $includeTimestamp = false): string
    {
        $filename = str_replace([' ', '/'], '_', $filename);

        if ($includeTimestamp) {
            $timestamp = now()->format('Y_m_d_H_i_s');
            return "{$filename}_{$timestamp}";
        }

        return $filename;
    }

    /**
     * Quick export method - shorthand for common use cases
     *
     * Usage: $model->quickExport('users', $data, 'exports.users')
     */
    public function quickExport(
        string $filename,
        $data,
        string $view
    ) {
        return $this->export($filename, $data, $view);
    }

    /**
     * Export with custom styling for professional output
     *
     * Usage: $model->styledExport('report', $data, [
     *     'view' => 'exports.excel.report',
     * ])
     */
    public function styledExport(
        string $filename,
        $data,
        array $config = []
    ) {
        $views = $config['views'] ?? $config['view'] ?? null;
        $queue = $config['queue'] ?? false;
        $options = array_diff_key($config, ['views' => null, 'view' => null, 'format' => null, 'queue' => null]);

        if (!$views) {
            throw new \InvalidArgumentException('View(s) must be provided in config');
        }

        return $this->export($filename, $data, $views, $queue, $options);
    }

    /**
     * Batch export multiple datasets
     *
     * Usage: $model->batchExport([
     *     ['filename' => 'users', 'data' => $users, 'view' => 'exports.users'],
     *     ['filename' => 'products', 'data' => $products, 'view' => 'exports.products'],
     * ])
     */
    public function batchExport(array $exports, array $options = [])
    {
        $results = [];

        foreach ($exports as $export) {
            $results[] = $this->export(
                $export['filename'],
                $export['data'],
                $export['view'],
                $export['queue'] ?? false,
                array_merge($options, $export['options'] ?? [])
            );
        }

        return $results;
    }

    /**
     * Export collection data with mapping/transformation
     *
     * Usage: $model->mapAndExport('users', $data, 'exports.users', [
     *     'name' => fn($user) => $user->first_name . ' ' . $user->last_name,
     *     'email' => fn($user) => strtolower($user->email),
     * ])
     */
    public function mapAndExport(
        string $filename,
        $data,
        string $view,
        array $mapping
    ) {
        $mappedData = collect($data)->map(function ($item) use ($mapping) {
            $row = [];
            foreach ($mapping as $key => $transformer) {
                $row[$key] = is_callable($transformer)
                    ? $transformer($item)
                    : $item[$transformer] ?? $item->{$transformer} ?? null;
            }
            return $row;
        });

        return $this->export($filename, $mappedData, $view);
    }
}

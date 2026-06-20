<?php

namespace App\Jobs;

use App\Support\CsvFromHtml;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        protected ?int $userId,
        protected string $filename,
        protected array $data,
        protected string $view,
        protected array $options = []
    ) {}

    public function handle(): void
    {
        $path = 'exports/' . $this->filename . '_' . now()->format('Y-m-d_His') . '.csv';

        $html = view($this->view, [
            'data' => collect($this->data),
            'forCsv' => true,
            'options' => $this->options,
        ])->render();

        $rows = CsvFromHtml::rows($html);

        $stream = fopen('php://temp', 'r+');
        CsvFromHtml::writeTo($stream, $rows);
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        Storage::disk('public')->put($path, $contents);

        Log::info('CSV export generated', [
            'user_id' => $this->userId,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}

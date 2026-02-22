<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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

        Excel::store(
            new class($this->data, $this->view, $this->options) implements \Maatwebsite\Excel\Concerns\FromView {
                public function __construct(
                    protected array $data,
                    protected string $view,
                    protected array $options
                ) {}

                public function view(): \Illuminate\Contracts\View\View
                {
                    return view($this->view, [
                        'data' => collect($this->data),
                        'forCsv' => true,
                        'options' => $this->options,
                    ]);
                }
            },
            $path,
            'public',
            \Maatwebsite\Excel\Excel::CSV
        );

        Log::info('CSV export generated', [
            'user_id' => $this->userId,
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    }
}

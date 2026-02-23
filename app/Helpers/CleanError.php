<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use TallStackUi\Foundation\Interactions\Toast;

class CleanError
{
    public static function show(
        string $message,
        ?\Throwable $e = null,
        array $context = [],
        ?Component $component = null
    ): void {
        Log::error($message, array_merge($context, [
            'exception' => $e,
        ]));

        (new Toast($component))
            ->error('Error', $message)
            ->send();
    }
}

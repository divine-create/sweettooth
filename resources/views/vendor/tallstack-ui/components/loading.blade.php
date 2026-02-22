@php
    $personalize = $classes();
@endphp

<div @if (!$delay)
         wire:loading
     @else
         wire:loading.delay{{ is_string($delay) && $delay !== "1" ? ".{$delay}" : "" }}
     @endif wire:loading.remove.delay="100ms" @if ($loading) wire:target="{{ $loading }}" @endif x-cloak {{ $attributes }} @class([
        $configurations['zIndex'],
        $personalize['wrapper.first'],
        $personalize['blur'] => $configurations['blur'] === true,
        $personalize['opacity'] => $configurations['opacity'] === true,
    ]) x-ref="loading" x-data="tallstackui_loading(@js($this->getName()), @js($configurations['overflow'] ?? false))" style="display: none;">
    <div class="{{ $personalize['wrapper.second'] }}">
        @if (!$text && empty($slot->toHtml()))
            <x-tallstack-ui::icon.generic.loading class="{{ $personalize['spinner'] }}" />
        @else
            <div class="{{ $personalize['text'] }}">
                {!! $text ?? $slot !!}
            </div>
        @endif
    </div>
</div>

@php
    use Illuminate\Support\Facades\Storage;
    $logoPath = \App\Helpers\Settings::businessConfiguration('logo_upload');
@endphp

@if($logoPath && Storage::disk('public')->exists($logoPath))
    <img src="{{ Storage::disk('public')->url($logoPath) }}" class="max-h-full max-w-full object-contain" />
@else
    <img src="/swtc.png" />
@endif

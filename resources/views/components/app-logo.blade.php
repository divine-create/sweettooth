@php
    use Illuminate\Support\Facades\Storage;
    $logoPath = \App\Helpers\Settings::businessConfiguration('logo_upload');
    $companyName = \App\Helpers\Settings::businessConfiguration('business_name', config('app.name', 'SweetTooth'));
@endphp

<div class="flex w-14 h-14 items-center justify-center rounded-md">
    @if($logoPath && Storage::disk('public')->exists($logoPath))
        <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ $companyName }}" class="max-h-full max-w-full object-contain" />
    @else
        <img src="/swtc.png" alt="{{ $companyName }}" />
    @endif
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ $companyName }}</span>
</div>

@php
    $classes = match($status) {
        'draft'     => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        'approved'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'paid'      => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        default     => 'bg-zinc-100 text-zinc-600',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $classes }}">
    {{ ucfirst($status) }}
</span>

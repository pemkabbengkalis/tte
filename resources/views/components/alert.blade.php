@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    $styles = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    ];
    $cls = $styles[$type] ?? $styles['info'];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition role="alert"
    {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', $cls]) }}>
    <div class="flex-1">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" @click="show = false"
            class="shrink-0 rounded p-0.5 leading-none opacity-60 transition hover:opacity-100" aria-label="Tutup">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>

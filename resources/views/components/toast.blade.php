@props(['message', 'type' => 'success'])

@php
    $styles = [
        'success' => ['ring' => 'ring-green-200', 'icon_bg' => 'bg-green-100', 'icon_color' => 'text-green-600', 'bar' => 'bg-green-500'],
        'info'    => ['ring' => 'ring-blue-200',  'icon_bg' => 'bg-blue-100',  'icon_color' => 'text-blue-600',  'bar' => 'bg-blue-500'],
        'warning' => ['ring' => 'ring-yellow-200','icon_bg' => 'bg-yellow-100','icon_color' => 'text-yellow-600','bar' => 'bg-yellow-500'],
        'error'   => ['ring' => 'ring-red-200',   'icon_bg' => 'bg-red-100',   'icon_color' => 'text-red-600',   'bar' => 'bg-red-500'],
    ];
    $s = $styles[$type] ?? $styles['info'];
@endphp

<div
    x-data="{ show: true, width: 100 }"
    x-init="
        let t = setInterval(() => { width = Math.max(0, width - 2) }, 100);
        setTimeout(() => { show = false; clearInterval(t) }, 5000);
    "
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-3 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-3 scale-95"
    class="fixed bottom-5 right-5 z-50 w-80 overflow-hidden rounded-xl bg-white shadow-xl ring-1 {{ $s['ring'] }}"
    role="alert"
>
    <div class="flex items-start gap-3 px-4 py-3.5">
        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $s['icon_bg'] }}">
            @if ($type === 'success')
                <svg class="h-4 w-4 {{ $s['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            @elseif ($type === 'error')
                <svg class="h-4 w-4 {{ $s['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            @elseif ($type === 'warning')
                <svg class="h-4 w-4 {{ $s['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            @else
                <svg class="h-4 w-4 {{ $s['icon_color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5h2M12 9v7m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @endif
        </div>

        <p class="flex-1 pt-0.5 text-sm leading-snug text-gray-700">{{ $message }}</p>

        <button
            @click="show = false"
            class="mt-0.5 shrink-0 rounded p-0.5 text-gray-400 transition hover:text-gray-600"
            aria-label="Tutup"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="h-0.5 bg-gray-100">
        <div class="h-full {{ $s['bar'] }} transition-none" :style="'width: ' + width + '%'"></div>
    </div>
</div>

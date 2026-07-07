@props([
    'href' => '#',
    'exact' => false,
    'active' => null, // string|array pola path eksplisit untuk status aktif
])

@php
    $target = ltrim($href, '/');

    if ($active !== null) {
        // Pola eksplisit diutamakan (mis. agar dashboard tetap aktif di halaman detail
        // namun tidak ikut aktif pada path saudara yang berbagi prefix).
        $patterns = array_filter((array) $active);
    } elseif ($exact) {
        $patterns = array_filter([$target]);
    } else {
        $patterns = array_filter([$target, $target . '/*']);
    }

    $isActive = ! empty($patterns) && request()->is(...$patterns);
@endphp


<a href="{{ $href }}"
wire:navigate
{{ $attributes->class([
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-primary-600 text-white shadow-sm' => $isActive,
    'text-primary-100 hover:bg-primary-700 hover:text-white' => !$isActive,
]) }}
>
{{ $slot }}
</a>

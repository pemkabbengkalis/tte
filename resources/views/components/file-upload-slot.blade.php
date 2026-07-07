@props(['label', 'hint' => null, 'required' => false])

<div class="space-y-1.5">
    <label class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div
        class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 transition focus-within:border-primary-400 focus-within:bg-white">
        {{ $slot }}
    </div>

    @if ($hint)
        <p class="text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>

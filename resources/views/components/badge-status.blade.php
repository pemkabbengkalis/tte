@props(['status'])

@php
    $enum =
        $status instanceof \App\Enums\StatusPermohonan
            ? $status
            : \App\Enums\StatusPermohonan::tryFrom((string) $status);

    $label = $enum?->label() ?? (string) $status;
    $color = $enum?->color() ?? 'gray';

    $map = [
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'yellow' => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'green' => 'bg-green-100 text-green-800 ring-green-200',
        'red' => 'bg-red-100 text-red-800 ring-red-200',
        'blue' => 'bg-blue-100 text-blue-800 ring-blue-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
    ];
    $cls = $map[$color] ?? $map['gray'];
@endphp

<span
    {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $cls]) }}>
    {{ $label }}
</span>

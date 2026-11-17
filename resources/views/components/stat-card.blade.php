@props(['label', 'value', 'color' => 'text-gray-900'])

<div class="rounded-lg bg-white p-5 shadow-sm">
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-3xl font-semibold {{ $color }}">{{ $value }}</p>
</div>

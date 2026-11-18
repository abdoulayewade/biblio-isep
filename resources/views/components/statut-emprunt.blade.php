@props(['statut'])

@php
    [$libelle, $classes, $point] = match ($statut) {
        'rendu' => ['Rendu', 'bg-gray-100 text-gray-700 ring-gray-500/20', 'bg-gray-400'],
        'en_retard' => ['En retard', 'bg-red-50 text-red-700 ring-red-600/20', 'bg-red-500'],
        default => ['En cours', 'bg-blue-50 text-blue-700 ring-blue-600/20', 'bg-blue-500'],
    };
@endphp

<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $point }}"></span>
    {{ $libelle }}
</span>

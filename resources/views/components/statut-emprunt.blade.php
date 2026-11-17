@props(['statut'])

@php
    [$libelle, $classes] = match ($statut) {
        'rendu' => ['Rendu', 'bg-gray-100 text-gray-700'],
        'en_retard' => ['En retard', 'bg-red-100 text-red-700'],
        default => ['En cours', 'bg-blue-100 text-blue-700'],
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $libelle }}
</span>

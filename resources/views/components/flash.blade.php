{{-- Messages de succès / d'erreur envoyés par les contrôleurs avec ->with('success'|'error', ...) --}}
@foreach (['success' => ['border-green-200 bg-green-50 text-green-800', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
           'error' => ['border-red-200 bg-red-50 text-red-800', 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z']] as $type => [$classes, $icone])
    @if (session($type))
        <div x-data="{ visible: true }" x-show="visible" x-transition
             class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm {{ $classes }}">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}" />
            </svg>
            <p class="flex-1">{{ session($type) }}</p>
            <button type="button" @click="visible = false" class="opacity-60 hover:opacity-100" aria-label="Fermer">&times;</button>
        </div>
    @endif
@endforeach

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des emprunts</h2>
            <a href="{{ route('admin.emprunts.create') }}">
                <x-primary-button type="button">+ Nouvel emprunt</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Onglets de filtre par statut --}}
            @php
                $onglets = ['' => 'Tous', 'en_cours' => 'En cours', 'en_retard' => 'En retard', 'rendus' => 'Rendus'];
            @endphp
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($onglets as $valeur => $libelle)
                    <a href="{{ route('admin.emprunts.index', array_filter(['statut' => $valeur, 'q' => request('q')])) }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium {{ ($statut ?? '') === $valeur ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-50' }}">
                        {{ $libelle }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.emprunts.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="statut" value="{{ $statut }}">
                <x-text-input name="q" value="{{ request('q') }}" placeholder="Nom du lecteur ou titre du livre..." class="w-full sm:w-80" />
                <x-primary-button>Rechercher</x-primary-button>
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Lecteur</th>
                            <th class="px-4 py-3">Livre</th>
                            <th class="px-4 py-3">Emprunté le</th>
                            <th class="px-4 py-3">Retour prévu</th>
                            <th class="px-4 py-3">Rendu le</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($emprunts as $emprunt)
                            <tr class="{{ $emprunt->est_en_retard ? 'bg-red-50/40' : '' }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $emprunt->user->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->livre->titre }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_emprunt->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_retour_prevue->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_retour_effective?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><x-statut-emprunt :statut="$emprunt->statut" /></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if (! $emprunt->date_retour_effective)
                                        <form method="POST" action="{{ route('admin.emprunts.retour', $emprunt) }}"
                                              onsubmit="return confirm('Confirmer le retour de ce livre ?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="font-medium text-indigo-600 hover:underline">Enregistrer le retour</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucun emprunt.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $emprunts->links() }}</div>
        </div>
    </div>
</x-app-layout>

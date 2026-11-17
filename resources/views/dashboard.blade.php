<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <p class="text-gray-600">Bonjour <strong>{{ auth()->user()->name }}</strong> 👋</p>

            @if (auth()->user()->isAdmin())
                {{-- ===================== ADMIN ===================== --}}
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
                    <x-stat-card label="Titres" :value="$stats['livres']" />
                    <x-stat-card label="Exemplaires" :value="$stats['exemplaires']" />
                    <x-stat-card label="Catégories" :value="$stats['categories']" />
                    <x-stat-card label="Lecteurs" :value="$stats['lecteurs']" />
                    <x-stat-card label="Emprunts en cours" :value="$stats['en_cours']" color="text-blue-600" />
                    <x-stat-card label="En retard" :value="$stats['en_retard']" :color="$stats['en_retard'] > 0 ? 'text-red-600' : 'text-gray-900'" />
                </div>

                @if ($stats['livres'] === 0)
                    {{-- Guide de démarrage quand la base est vide --}}
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-6">
                        <h3 class="font-semibold text-indigo-900">Pour bien démarrer</h3>
                        <ol class="mt-3 list-inside list-decimal space-y-1 text-sm text-indigo-900">
                            <li><a href="{{ route('admin.categories.create') }}" class="underline">Créez vos catégories</a> (Informatique, Roman...)</li>
                            <li><a href="{{ route('admin.livres.create') }}" class="underline">Ajoutez vos livres</a></li>
                            <li><a href="{{ route('admin.lecteurs.create') }}" class="underline">Inscrivez des lecteurs</a></li>
                            <li><a href="{{ route('admin.emprunts.create') }}" class="underline">Enregistrez un emprunt</a></li>
                        </ol>
                    </div>
                @endif

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-800">Emprunts en retard</h3>
                        <a href="{{ route('admin.emprunts.index', ['statut' => 'en_retard']) }}" class="text-sm text-indigo-600 hover:underline">Tout voir</a>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($retards as $emprunt)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                                <span>
                                    <strong>{{ $emprunt->user->name }}</strong> — {{ $emprunt->livre->titre }}
                                </span>
                                <span class="text-red-600">
                                    à rendre le {{ $emprunt->date_retour_prevue->format('d/m/Y') }}
                                    ({{ (int) $emprunt->date_retour_prevue->diffInDays(today()) }} j de retard)
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-gray-500">Aucun retard 🎉</li>
                        @endforelse
                    </ul>
                </div>
            @else
                {{-- ===================== LECTEUR ===================== --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-stat-card label="Emprunts en cours" :value="$stats['en_cours'].' / '.$stats['max']" color="text-blue-600" />
                    <x-stat-card label="En retard" :value="$stats['en_retard']" :color="$stats['en_retard'] > 0 ? 'text-red-600' : 'text-gray-900'" />
                    <x-stat-card label="Total emprunté" :value="$stats['total']" />
                </div>

                @if ($stats['en_retard'] > 0)
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        Vous avez {{ $stats['en_retard'] }} livre(s) en retard. Merci de les rapporter à la bibliothèque.
                    </div>
                @endif

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-800">Mes livres en cours</h3>
                        <a href="{{ route('emprunts.mes') }}" class="text-sm text-indigo-600 hover:underline">Historique complet</a>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($empruntsEnCours as $emprunt)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                                <span><strong>{{ $emprunt->livre->titre }}</strong> — {{ $emprunt->livre->auteur }}</span>
                                <span class="flex items-center gap-2">
                                    à rendre avant le {{ $emprunt->date_retour_prevue->format('d/m/Y') }}
                                    <x-statut-emprunt :statut="$emprunt->statut" />
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-gray-500">
                                Aucun emprunt en cours. <a href="{{ route('livres.index') }}" class="text-indigo-600 underline">Parcourir le catalogue</a>
                            </li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

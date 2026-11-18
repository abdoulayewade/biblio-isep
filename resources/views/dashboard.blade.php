<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Bandeau de bienvenue --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-7 text-white shadow-lg shadow-indigo-900/10 sm:px-8">
                <div class="absolute -right-10 -top-16 h-48 w-48 rounded-full bg-white/10"></div>
                <div class="absolute -bottom-20 right-24 h-40 w-40 rounded-full bg-white/5"></div>
                <div class="relative flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-100">{{ ucfirst(now()->locale('fr')->translatedFormat('l j F Y')) }}</p>
                        <h3 class="mt-1 text-2xl font-bold">Bonjour, {{ auth()->user()->name }} 👋</h3>
                        <p class="mt-1 text-indigo-100">
                            {{ auth()->user()->isAdmin() ? 'Voici l\'activité de la bibliothèque aujourd\'hui.' : 'Retrouvez ici vos emprunts en cours.' }}
                        </p>
                    </div>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.emprunts.create') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">
                            + Nouvel emprunt
                        </a>
                    @else
                        <a href="{{ route('livres.index') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">
                            Parcourir le catalogue
                        </a>
                    @endif
                </div>
            </div>

            @if (auth()->user()->isAdmin())
                {{-- ===================== ADMIN ===================== --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-stat-card label="Titres" :value="$stats['livres']" icon="livre" />
                    <x-stat-card label="Exemplaires" :value="$stats['exemplaires']" icon="pile" accent="bg-violet-50 text-violet-600" />
                    <x-stat-card label="Catégories" :value="$stats['categories']" icon="tag" accent="bg-amber-50 text-amber-600" />
                    <x-stat-card label="Lecteurs" :value="$stats['lecteurs']" icon="users" accent="bg-emerald-50 text-emerald-600" />
                    <x-stat-card label="Emprunts en cours" :value="$stats['en_cours']" icon="echange" accent="bg-blue-50 text-blue-600" color="text-blue-600" />
                    <x-stat-card label="En retard" :value="$stats['en_retard']" icon="horloge"
                                 :accent="$stats['en_retard'] > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'"
                                 :color="$stats['en_retard'] > 0 ? 'text-red-600' : 'text-gray-900'" />
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

                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
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
                    <x-stat-card label="Emprunts en cours" :value="$stats['en_cours'].' / '.$stats['max']" icon="echange" accent="bg-blue-50 text-blue-600" color="text-blue-600" />
                    <x-stat-card label="En retard" :value="$stats['en_retard']" icon="horloge"
                                 :accent="$stats['en_retard'] > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'"
                                 :color="$stats['en_retard'] > 0 ? 'text-red-600' : 'text-gray-900'" />
                    <x-stat-card label="Total emprunté" :value="$stats['total']" icon="archive" accent="bg-violet-50 text-violet-600" />
                </div>

                @if ($stats['en_retard'] > 0)
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        Vous avez {{ $stats['en_retard'] }} livre(s) en retard. Merci de les rapporter à la bibliothèque.
                    </div>
                @endif

                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
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

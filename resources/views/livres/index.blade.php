<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Catalogue des livres</h2>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.livres.create') }}">
                    <x-primary-button type="button">+ Ajouter un livre</x-primary-button>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Recherche + filtre par catégorie --}}
            <form method="GET" action="{{ route('livres.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
                <x-text-input name="q" value="{{ request('q') }}" placeholder="Titre, auteur ou ISBN..." class="w-full sm:w-80" />
                <select name="categorie" class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie->id }}" @selected(request('categorie') == $categorie->id)>{{ $categorie->nom }}</option>
                    @endforeach
                </select>
                <x-primary-button>Rechercher</x-primary-button>
                @if (request()->hasAny(['q', 'categorie']))
                    <a href="{{ route('livres.index') }}" class="self-center text-sm text-gray-600 underline">Réinitialiser</a>
                @endif
            </form>

            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Titre</th>
                            <th class="px-4 py-3">Auteur</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">ISBN</th>
                            <th class="px-4 py-3">Disponibilité</th>
                            @if (auth()->user()->isAdmin())
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($livres as $livre)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-8 shrink-0 items-center justify-center rounded bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold text-white shadow-sm">
                                            {{ mb_strtoupper(mb_substr($livre->titre, 0, 1)) }}
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ $livre->titre }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $livre->auteur }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $livre->category->nom }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $livre->isbn }}</td>
                                <td class="px-4 py-3">
                                    @if ($livre->disponible > 0)
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            {{ $livre->disponible }} / {{ $livre->quantite }} disponible(s)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Indisponible (0 / {{ $livre->quantite }})
                                        </span>
                                    @endif
                                </td>
                                @if (auth()->user()->isAdmin())
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            @if ($livre->disponible > 0)
                                                <a href="{{ route('admin.emprunts.create', ['livre_id' => $livre->id]) }}"
                                                   class="rounded-md px-2 py-1 text-xs font-semibold text-green-700 hover:bg-green-50">Prêter</a>
                                            @endif
                                            <a href="{{ route('admin.livres.edit', $livre) }}"
                                               class="rounded-md px-2 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Modifier</a>
                                            <form method="POST" action="{{ route('admin.livres.destroy', $livre) }}" class="inline"
                                                  onsubmit="return confirm('Supprimer ce livre ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun livre trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $livres->links() }}</div>
        </div>
    </div>
</x-app-layout>

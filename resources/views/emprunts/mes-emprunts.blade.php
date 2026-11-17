<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes emprunts</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Livre</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Emprunté le</th>
                            <th class="px-4 py-3">À rendre avant le</th>
                            <th class="px-4 py-3">Rendu le</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($emprunts as $emprunt)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $emprunt->livre->titre }}
                                    <span class="block text-xs font-normal text-gray-500">{{ $emprunt->livre->auteur }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->livre->category->nom }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_emprunt->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_retour_prevue->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $emprunt->date_retour_effective?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><x-statut-emprunt :statut="$emprunt->statut" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    Vous n'avez encore emprunté aucun livre.
                                    <a href="{{ route('livres.index') }}" class="text-indigo-600 underline">Voir le catalogue</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $emprunts->links() }}</div>
        </div>
    </div>
</x-app-layout>

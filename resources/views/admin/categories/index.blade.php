<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Catégories</h2>
            <a href="{{ route('admin.categories.create') }}">
                <x-primary-button type="button">+ Nouvelle catégorie</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Nombre de livres</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $category->nom }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <a href="{{ route('livres.index', ['categorie' => $category->id]) }}" class="hover:underline">
                                        {{ $category->livres_count }} livre(s)
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="rounded-md px-2 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Modifier</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline"
                                          onsubmit="return confirm('Supprimer cette catégorie ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">Aucune catégorie.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

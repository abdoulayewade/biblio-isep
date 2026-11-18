<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Lecteurs</h2>
            <a href="{{ route('admin.lecteurs.create') }}">
                <x-primary-button type="button">+ Inscrire un lecteur</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" action="{{ route('admin.lecteurs.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
                <x-text-input name="q" value="{{ request('q') }}" placeholder="Nom ou e-mail..." class="w-full sm:w-80" />
                <x-primary-button>Rechercher</x-primary-button>
            </form>

            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">E-mail</th>
                            <th class="px-4 py-3">Emprunts en cours</th>
                            <th class="px-4 py-3">Total emprunts</th>
                            <th class="px-4 py-3">Inscrit le</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($lecteurs as $lecteur)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $lecteur->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lecteur->email }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lecteur->emprunts_en_cours_count }} / {{ \App\Models\Emprunt::MAX_EN_COURS }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lecteur->emprunts_count }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lecteur->created_at->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.emprunts.index', ['q' => $lecteur->name]) }}" class="text-indigo-600 hover:underline">Voir ses emprunts</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun lecteur inscrit pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $lecteurs->links() }}</div>
        </div>
    </div>
</x-app-layout>

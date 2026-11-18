<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Nouvel emprunt</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="mb-6 rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    Durée du prêt : <strong>{{ \App\Models\Emprunt::DUREE_JOURS }} jours</strong>.
                    Maximum <strong>{{ \App\Models\Emprunt::MAX_EN_COURS }} emprunts en cours</strong> par lecteur.
                </p>

                <form method="POST" action="{{ route('admin.emprunts.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="user_id" value="Lecteur" />
                        <select id="user_id" name="user_id" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Choisir un lecteur --</option>
                            @foreach ($lecteurs as $lecteur)
                                <option value="{{ $lecteur->id }}" @selected(old('user_id') == $lecteur->id)
                                        @disabled($lecteur->emprunts_en_cours_count >= \App\Models\Emprunt::MAX_EN_COURS)>
                                    {{ $lecteur->name }} ({{ $lecteur->emprunts_en_cours_count }}/{{ \App\Models\Emprunt::MAX_EN_COURS }} en cours)
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="livre_id" value="Livre" />
                        <select id="livre_id" name="livre_id" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Choisir un livre --</option>
                            @foreach ($livres as $livre)
                                <option value="{{ $livre->id }}" @selected(old('livre_id', $livreSelectionne) == $livre->id)
                                        @disabled($livre->disponible <= 0)>
                                    {{ $livre->titre }} — {{ $livre->disponible }} dispo.
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('livre_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer l'emprunt</x-primary-button>
                        <a href="{{ route('admin.emprunts.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- Formulaire partagé entre "Ajouter" et "Modifier" --}}
<div class="space-y-5">
    <div>
        <x-input-label for="titre" value="Titre" />
        <x-text-input id="titre" name="titre" class="mt-1 block w-full" :value="old('titre', $livre->titre)" required autofocus />
        <x-input-error :messages="$errors->get('titre')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="auteur" value="Auteur" />
        <x-text-input id="auteur" name="auteur" class="mt-1 block w-full" :value="old('auteur', $livre->auteur)" required />
        <x-input-error :messages="$errors->get('auteur')" class="mt-2" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="isbn" value="ISBN" />
            <x-text-input id="isbn" name="isbn" class="mt-1 block w-full" :value="old('isbn', $livre->isbn)" required />
            <x-input-error :messages="$errors->get('isbn')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="quantite" value="Nombre d'exemplaires" />
            <x-text-input id="quantite" name="quantite" type="number" min="1" class="mt-1 block w-full" :value="old('quantite', $livre->quantite ?? 1)" required />
            <x-input-error :messages="$errors->get('quantite')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="category_id" value="Catégorie" />
        <select id="category_id" name="category_id" required
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Choisir --</option>
            @foreach ($categories as $categorie)
                <option value="{{ $categorie->id }}" @selected(old('category_id', $livre->category_id) == $categorie->id)>
                    {{ $categorie->nom }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>
</div>

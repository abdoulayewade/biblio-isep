<div>
    <x-input-label for="nom" value="Nom de la catégorie" />
    <x-text-input id="nom" name="nom" class="mt-1 block w-full" :value="old('nom', $category->nom)" required autofocus />
    <x-input-error :messages="$errors->get('nom')" class="mt-2" />
</div>

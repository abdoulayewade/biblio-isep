<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Ajouter un livre</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <form method="POST" action="{{ route('admin.livres.store') }}">
                    @csrf
                    @include('admin.livres._form')

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('livres.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BiblioISEP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen">
            {{-- Panneau de présentation (grands écrans uniquement) --}}
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-12 text-white lg:flex">
                <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10"></div>
                <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white/5"></div>

                <a href="/" class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <x-application-logo class="h-6 w-6 text-white" />
                    </span>
                    <span class="text-2xl font-bold tracking-tight">BiblioISEP</span>
                </a>

                <div class="relative max-w-md">
                    <h1 class="text-4xl font-bold leading-tight">La bibliothèque de l'ISEP, à portée de clic.</h1>
                    <p class="mt-4 text-lg text-indigo-100">Consultez le catalogue, suivez vos emprunts et ne ratez plus aucune date de retour.</p>

                    <ul class="mt-8 space-y-3 text-indigo-50">
                        @foreach (['Catalogue avec recherche par titre, auteur ou ISBN', 'Disponibilité des exemplaires en temps réel', 'Suivi des emprunts et des retards'] as $point)
                            <li class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-sm text-indigo-200">&copy; {{ date('Y') }} BiblioISEP</p>
            </div>

            {{-- Formulaire --}}
            <div class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
                <a href="/" class="mb-8 flex flex-col items-center gap-2 lg:hidden">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-lg">
                        <x-application-logo class="h-8 w-8 text-white" />
                    </span>
                    <span class="text-2xl font-bold tracking-tight text-gray-900">BiblioISEP</span>
                </a>

                <div class="w-full max-w-md rounded-2xl bg-white px-8 py-8 shadow-xl shadow-indigo-900/5 ring-1 ring-gray-900/5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

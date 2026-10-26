@props(['title' => null, 'description' => null])

{{-- Layout voor openbare pagina's (openbare recepten, ontdekken) voor bezoekers zonder huishouden --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f0bcd0">
        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'Takkie') }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        <link rel="icon" href="{{ asset('icon-192.png') }}">
        {{-- PWA (M9): installeerbaar, zodat meldingen ook op iOS werken --}}
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Takkie">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen">
        <x-splash />
        <x-toaster />

        <div class="min-h-screen bg-screen">
            <header class="sticky top-0 z-30 border-b border-border bg-background/85 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 lg:px-8">
                    <a href="{{ route('recipes.discover') }}" wire:navigate>
                        <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-16">
                    </a>
                    @auth
                        <x-button size="sm" class="rounded-full" href="/">Naar Takkie</x-button>
                    @else
                        <x-button size="sm" class="rounded-full" :href="route('login')">Inloggen</x-button>
                    @endauth
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>

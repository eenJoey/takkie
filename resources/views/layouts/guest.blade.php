@props(['title' => null, 'width' => 'max-w-md'])

{{-- Layout voor de inlogschermen: kaart op een gradient, zoals vinkie's /login, /forgot-password en /reset-password --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f0bcd0">
        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'Takkie') }}</title>
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
        <x-toaster />

        <div class="flex min-h-screen items-center justify-center bg-linear-to-br from-background via-secondary/40 to-accent/30 px-4 py-12">
            <div class="w-full {{ $width }}">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>

@props(['title' => null])

{{-- Layout voor de inlogschermen: kaart op een gradient, zoals vinkie's /login, /forgot-password en /reset-password --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f0bcd0">
        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'Takkie') }}</title>
        <link rel="icon" href="{{ asset('images/takkie-logo.png') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen">
        <x-toaster />

        <div class="flex min-h-screen items-center justify-center bg-linear-to-br from-background via-secondary/40 to-accent/30 px-4 py-12">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>

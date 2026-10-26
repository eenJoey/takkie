@props(['title' => null])

@php
    // Navigatie volgens vinkie's AppShell. Items komen erbij per mijlpaal (zie MIGRATIE-PLAN.md, sectie k).
    $navItems = [
        ['route' => 'lists.index', 'active' => 'lists.*', 'label' => 'Lijstjes', 'icon' => 'list-checks'],
    ];
    $household = app(\App\Support\CurrentHousehold::class)->get();
@endphp

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
        <div class="flex min-h-screen bg-screen">
            {{-- Desktop sidebar --}}
            <aside class="hidden w-64 flex-col border-r border-sidebar-border bg-sidebar lg:flex">
                <div class="p-6">
                    <a href="{{ route('lists.index') }}" wire:navigate>
                        <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-24">
                    </a>
                </div>
                <nav class="flex-1 space-y-1 px-3">
                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" wire:navigate @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm' => $active,
                            'text-sidebar-foreground hover:bg-sidebar-accent' => ! $active,
                        ])>
                            <x-icon :name="$item['icon']" class="size-5" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            {{-- Main --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 border-b border-border bg-background/85 backdrop-blur">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 lg:px-8">
                        <a href="{{ route('lists.index') }}" wire:navigate class="lg:hidden">
                            <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-16">
                        </a>
                        <h2 class="truncate text-lg font-bold">{{ $household?->name ?? 'Welkom' }}</h2>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-md flex-1 px-4 py-6 pb-28 lg:pb-10">
                    {{ $slot }}
                </main>

                {{-- Mobiele bottom-nav --}}
                <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card pb-[env(safe-area-inset-bottom)] lg:hidden">
                    <div class="grid" style="grid-template-columns: repeat({{ count($navItems) }}, minmax(0, 1fr))">
                        @foreach ($navItems as $item)
                            @php $active = request()->routeIs($item['active']); @endphp
                            <a href="{{ route($item['route']) }}" wire:navigate @class([
                                'flex flex-col items-center justify-center gap-0.5 py-2.5 text-[11px] font-semibold',
                                'text-primary-foreground' => $active,
                                'text-muted-foreground' => ! $active,
                            ])>
                                <x-icon :name="$item['icon']" @class(['size-5', 'text-primary' => $active]) />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            </div>
        </div>

        @livewireScripts
    </body>
</html>

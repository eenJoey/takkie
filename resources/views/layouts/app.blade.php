@props(['title' => null])

@php
    // Navigatie volgens vinkie's AppShell. Items komen erbij per mijlpaal (zie MIGRATIE-PLAN.md, sectie k).
    $navItems = [
        ['route' => 'lists.index', 'active' => 'lists.*', 'label' => 'Lijstjes', 'icon' => 'list-checks'],
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => 'Vandaag', 'icon' => 'home'],
        ['route' => 'rewards', 'active' => 'rewards', 'label' => 'Beloningen', 'icon' => 'gift'],
        ['route' => 'household', 'active' => 'household', 'label' => 'Huishouden', 'icon' => 'users'],
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
        <x-toaster />

        <div class="flex min-h-screen bg-screen">
            {{-- Desktop sidebar --}}
            <aside class="hidden w-64 flex-col border-r border-sidebar-border bg-sidebar lg:flex">
                <div class="p-6">
                    <a href="{{ route('lists.index') }}" wire:navigate>
                        <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-24">
                    </a>
                </div>
                <div class="px-4 pb-4">
                    <livewire:shared.household-switcher key="switcher-sidebar" />
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
                <div class="border-t border-sidebar-border p-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-muted-foreground hover:bg-sidebar-accent">
                            <x-icon name="log-out" />
                            Uitloggen
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 border-b border-border bg-background/85 backdrop-blur">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 lg:px-8">
                        <a href="{{ route('lists.index') }}" wire:navigate class="lg:hidden">
                            <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-16">
                        </a>
                        <div class="min-w-0 max-w-[200px] flex-1 lg:hidden">
                            <livewire:shared.household-switcher key="switcher-header" />
                        </div>
                        <h2 class="hidden min-w-0 flex-1 truncate text-lg font-bold lg:block">{{ $household?->name ?? 'Welkom' }}</h2>
                        <livewire:shared.points-badge key="points-badge" />
                        {{-- Op mobiel tijdelijk hier; in M8 verhuist uitloggen naar de profielpagina (zoals vinkie lovable) --}}
                        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                            @csrf
                            <x-button type="submit" variant="icon" aria-label="Uitloggen">
                                <x-icon name="log-out" class="size-5" />
                            </x-button>
                        </form>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 pb-28 lg:px-8 lg:pb-10">
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

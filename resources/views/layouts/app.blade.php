@props(['title' => null])

@php
    // Navigatie volgens vinkie's AppShell; de tabbladen staan in App\Enums\StartPage.
    $navItems = \App\Enums\StartPage::cases();
    $bottomNavItems = array_values(array_filter($navItems, fn ($page) => $page->inBottomNav()));
    $household = app(\App\Support\CurrentHousehold::class)->get();
@endphp

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
        <x-splash />
        <x-toaster />

        <div class="flex min-h-screen bg-screen">
            {{-- Desktop sidebar --}}
            <aside class="hidden w-64 flex-col border-r border-sidebar-border bg-sidebar lg:flex">
                <div class="p-6">
                    <a href="{{ route('home') }}" wire:navigate>
                        <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-24">
                    </a>
                </div>
                <div class="px-4 pb-4">
                    <livewire:shared.household-switcher key="switcher-sidebar" />
                </div>
                <nav class="flex-1 space-y-1 px-3">
                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item->activePattern()); @endphp
                        <a href="{{ route($item->route()) }}" wire:navigate @class([
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm' => $active,
                            'text-sidebar-foreground hover:bg-sidebar-accent' => ! $active,
                        ])>
                            <x-icon :name="$item->icon()" class="size-5" />
                            {{ $item->label() }}
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
                        <a href="{{ route('home') }}" wire:navigate class="lg:hidden">
                            <img src="{{ asset('images/takkie-text-logo.png') }}" alt="Takkie" class="h-auto w-16">
                        </a>
                        <div class="min-w-0 max-w-[200px] flex-1 lg:hidden">
                            <livewire:shared.household-switcher key="switcher-header" />
                        </div>
                        <h2 class="hidden min-w-0 flex-1 truncate text-lg font-bold lg:block">{{ $household?->name ?? 'Welkom' }}</h2>
                        <livewire:shared.points-badge key="points-badge" />
                        {{-- Avatar + naam → profiel (vinkie lovable); uitloggen staat op de profielpagina --}}
                        <a href="{{ route('profile') }}" wire:navigate aria-label="Profiel"
                            class="flex shrink-0 items-center gap-2 rounded-full py-1 pr-1 pl-1 transition hover:bg-accent/30 sm:pr-3">
                            <x-avatar :user="auth()->user()" class="size-9 text-sm" />
                            <span class="hidden max-w-[140px] truncate text-sm font-semibold sm:block">{{ auth()->user()->name }}</span>
                        </a>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 pb-28 lg:px-8 lg:pb-10">
                    {{ $slot }}
                </main>

                {{-- Mobiele bottom-nav --}}
                <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card pb-[env(safe-area-inset-bottom)] lg:hidden">
                    <div class="grid" style="grid-template-columns: repeat({{ count($bottomNavItems) }}, minmax(0, 1fr))">
                        @foreach ($bottomNavItems as $item)
                            @php $active = request()->routeIs($item->activePattern()); @endphp
                            <a href="{{ route($item->route()) }}" wire:navigate @class([
                                'flex flex-col items-center justify-center gap-0.5 py-2.5 text-[11px] font-semibold',
                                'text-primary-foreground' => $active,
                                'text-muted-foreground' => ! $active,
                            ])>
                                <x-icon :name="$item->icon()" @class(['size-5', 'text-primary' => $active]) />
                                {{ $item->label() }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            </div>
        </div>

        @livewireScripts
    </body>
</html>

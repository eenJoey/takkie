@php
    $user = auth()->user();
    $hero = $this->hero;
    $best = $hero->best();
@endphp

<div class="space-y-6">
    <x-page-header title="Mijn held" subtitle="Taken afvinken en koken levert XP op in je klassen." />

    <div class="grid items-start gap-6 lg:grid-cols-3">
        {{-- held --}}
        <section class="flex items-center gap-4 rounded-3xl bg-card p-6 shadow-card lg:col-span-2">
            <x-avatar :user="$user" class="size-16 text-xl" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-lg font-extrabold">{{ $user->name }}</p>
                <p class="truncate text-sm text-muted-foreground">
                    @if ($best)
                        {{ $best->rank()['emoji'] ?? $best->class->emoji() }} {{ $best->fullTitle() }}
                    @else
                        Nog een beginnende held. Vink een taak met een klasse af!
                    @endif
                </p>
            </div>
            <div class="shrink-0 text-center">
                <p class="text-3xl font-extrabold tabular-nums text-primary">{{ $hero->level() }}</p>
                <p class="text-xs font-semibold text-muted-foreground">held-level</p>
            </div>
        </section>

        {{-- koken --}}
        <section class="space-y-3 rounded-3xl bg-card p-6 shadow-card">
            <h2 class="font-bold">🍳 Ik heb gekookt</h2>
            @if ($this->cookedToday)
                <p class="text-sm text-muted-foreground">Vandaag al gekookt. Morgen weer Chef-XP!</p>
            @else
                <x-select aria-label="Wat heb je gekookt?" wire:model="recipeId">
                    <option value="">Iets anders (+10 XP)</option>
                    @foreach ($this->recipes as $recipe)
                        <option value="{{ $recipe->id }}">{{ $recipe->title }}</option>
                    @endforeach
                </x-select>
                <p class="text-xs text-muted-foreground">
                    Recept van het weekmenu +15 XP · voor het eerst gekookt +20 XP.
                    @if ($menu)
                        Vandaag op het menu: {{ $menu->recipe?->title ?? $menu->title }}.
                    @endif
                </p>
                <x-button class="w-full rounded-full" wire:click="cook" wire:loading.attr="disabled" wire:target="cook">
                    <span wire:loading.remove wire:target="cook">Ik heb gekookt!</span>
                    <span wire:loading wire:target="cook">Bezig…</span>
                </x-button>
            @endif
        </section>
    </div>

    {{-- klassen --}}
    <section>
        <h2 class="mb-3 text-lg font-bold">Klassen</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($hero->classes as $progress)
                @php $rank = $progress->rank(); @endphp
                <div wire:key="class-{{ $progress->class->value }}" @class([
                    'rounded-2xl bg-card p-4 shadow-card',
                    'ring-2 ring-warning/60' => $progress->canPrestige(),
                ])>
                    <div class="flex items-start gap-3">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-secondary text-2xl" aria-hidden="true">{{ $progress->class->emoji() }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-bold">{{ $progress->class->label() }}</h3>
                                <span class="text-sm font-extrabold tabular-nums">Level {{ $progress->level }}</span>
                            </div>
                            <p class="truncate text-sm text-muted-foreground">{{ $progress->title() }}</p>
                            @if ($rank)
                                <p class="mt-1 inline-flex items-center gap-1 rounded-full bg-warning/20 px-2 py-0.5 text-xs font-bold text-warning-foreground">
                                    {{ $rank['emoji'] }} {{ $rank['label'] }} · +{{ $progress->bonusPercent() }}% XP
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-secondary" role="progressbar"
                        aria-label="Voortgang {{ $progress->class->label() }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress->percent() }}">
                        <div @class(['h-full rounded-full transition-all', 'bg-warning' => $progress->canPrestige(), 'bg-primary' => ! $progress->canPrestige()])
                            style="width: {{ $progress->percent() }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground tabular-nums">
                        @if ($progress->canPrestige())
                            Maximaal level bereikt!
                        @else
                            {{ $progress->xpIntoLevel() }} / {{ $progress->xpForNext() }} XP naar level {{ $progress->level + 1 }}
                        @endif
                    </p>

                    @if ($progress->canPrestige())
                        <x-button class="mt-3 w-full rounded-full" wire:click="prestige('{{ $progress->class->value }}')"
                            wire:confirm="Prestige doen? Je gaat in {{ $progress->class->label() }} terug naar level 1, met een prestige-rang en voortaan meer XP."
                            wire:loading.attr="disabled" wire:target="prestige">
                            ⭐ Prestige
                        </x-button>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- gilde --}}
        <section>
            <h2 class="mb-3 flex items-center justify-between text-lg font-bold">
                <span>🛡️ Gilde</span>
                <span class="text-sm font-semibold text-muted-foreground">Gilde-level {{ $this->guildLevel }}</span>
            </h2>
            <div class="divide-y divide-border rounded-2xl bg-card shadow-card">
                @foreach ($this->guild as $member)
                    <div wire:key="guild-{{ $member['user']->id }}" class="flex items-center gap-3 px-4 py-3">
                        <x-avatar :user="$member['user']" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">
                                {{ $member['user']->name }}
                                @if ($member['user']->id === $user->id)
                                    <span class="text-xs text-muted-foreground">(jij)</span>
                                @endif
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ $member['best'] ? ($member['best']->class->emoji().' '.$member['best']->fullTitle()) : 'Nog geen XP' }}
                            </p>
                        </div>
                        <span class="text-sm font-extrabold tabular-nums">{{ $member['level'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- recent --}}
        <section>
            <h2 class="mb-3 text-lg font-bold">Laatst verdiend</h2>
            @if ($this->recent->isEmpty())
                <p class="rounded-2xl bg-card p-4 text-sm text-muted-foreground shadow-card">Nog niets. Geef je taken een klasse, dan levert afvinken XP op.</p>
            @else
                <div class="divide-y divide-border rounded-2xl bg-card shadow-card">
                    @foreach ($this->recent as $event)
                        <div wire:key="xp-{{ $event->id }}" class="flex items-center gap-3 px-4 py-2.5 text-sm">
                            <span aria-hidden="true">{{ $event->hero_class->emoji() }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $event->label ?? $event->hero_class->label() }}</span>
                            <span class="font-bold tabular-nums text-primary">+{{ $event->amount }} XP</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>

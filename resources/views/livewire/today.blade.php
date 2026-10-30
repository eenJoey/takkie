<div class="space-y-6">
    <div>
        <p class="text-muted-foreground">{{ $this->greeting() }} 👋</p>
        <h1 class="text-3xl font-extrabold">Wat staat er op de planning?</h1>
        @php $streak = $this->streak(); @endphp
        @if ($streak > 0)
            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-card px-3 py-1 text-sm font-semibold shadow-card">
                🔥 {{ $streak }} {{ $streak === 1 ? 'dag' : 'dagen' }} op rij — hou vol!
            </p>
        @endif
    </div>

    @if ($this->tasksList)
        {{-- vinkie QuickCards --}}
        <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ route('tasks') }}" wire:navigate
            class="group flex items-center justify-between rounded-2xl bg-card p-4 shadow-card transition hover:shadow-soft">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-warm text-primary-foreground">
                    <x-icon name="list-checks" class="size-5" />
                </div>
                <span class="font-bold">Alle taken</span>
            </div>
            <x-icon name="arrow-right" class="text-muted-foreground transition group-hover:translate-x-1" />
        </a>
        <a href="{{ route('rewards') }}" wire:navigate
            class="group flex items-center justify-between rounded-2xl bg-card p-4 shadow-card transition hover:shadow-soft">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-warm text-primary-foreground">
                    <x-icon name="gift" class="size-5" />
                </div>
                <span class="font-bold">Beloningen</span>
            </div>
            <x-icon name="arrow-right" class="text-muted-foreground transition group-hover:translate-x-1" />
        </a>
        </div>
    @endif

    <div>
        <div class="mb-3 flex items-center justify-between gap-2">
            <button type="button" wire:click="previousDay" aria-label="Vorige dag"
                class="flex size-9 items-center justify-center rounded-full bg-card shadow-card hover:bg-accent/30">
                <x-icon name="chevron-left" />
            </button>
            <div class="text-center">
                <h2 class="text-lg font-bold first-letter:uppercase">{{ $this->label() }}</h2>
                @if ($offset !== 0)
                    <button type="button" wire:click="backToToday" class="text-xs font-semibold text-link hover:underline">Terug naar vandaag</button>
                @endif
            </div>
            <button type="button" wire:click="nextDay" aria-label="Volgende dag"
                class="flex size-9 items-center justify-center rounded-full bg-card shadow-card hover:bg-accent/30">
                <x-icon name="chevron-right" />
            </button>
        </div>

        @php $meal = $this->meal(); @endphp
        <a href="{{ $meal?->recipe ? route('recipes.show', $meal->recipe->slug) : route('weekmenu') }}" wire:navigate
            class="mb-3 flex items-center gap-3 rounded-2xl bg-card p-4 shadow-card transition hover:shadow-soft">
            <span class="text-2xl" aria-hidden="true">🍽️</span>
            <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold text-muted-foreground">Eten</span>
                <span class="block truncate font-bold">{{ $meal?->recipe?->title ?? $meal?->title ?? 'Nog niets gepland' }}</span>
            </span>
            <x-icon name="arrow-right" class="text-muted-foreground" />
        </a>

        @if ($this->tasks->isEmpty())
            <x-empty-state emoji="✨" title="Niks te doen" description="Geniet ervan, of voeg een nieuwe taak toe." />
        @else
            @php $future = $offset > 0; @endphp
            <div class="grid gap-2.5 lg:grid-cols-2">
                @foreach ($this->tasks as $task)
                    @php
                        $overdue = $task->isOverdue($this->today);
                        $projected = $future && ! $overdue;
                    @endphp
                    <div wire:key="today-{{ $task->id }}" @class([
                        'flex items-center gap-3 rounded-2xl bg-card p-4 shadow-card',
                        'ring-2 ring-destructive/40' => $overdue,
                        'opacity-70' => $projected,
                    ])>
                        <button type="button" wire:click="complete({{ $task->id }})" @disabled($future) aria-label="Afvinken"
                            class="group flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-border transition hover:border-success hover:bg-success/10 disabled:cursor-not-allowed disabled:hover:border-border disabled:hover:bg-transparent">
                            <x-icon name="check" class="size-5 text-transparent group-hover:text-success group-disabled:text-transparent" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold break-words">{{ $task->title }}</h3>
                            @if ($overdue)
                                <p class="text-xs font-semibold text-destructive">Te laat — stond op {{ $task->dueLabel() }}</p>
                            @elseif ($projected)
                                <p class="text-xs text-muted-foreground">Gepland (herhaling)</p>
                            @endif
                        </div>
                        @if ($task->hero_class)
                            <span class="text-lg" title="{{ $task->hero_class->label() }}" aria-label="Klasse {{ $task->hero_class->label() }}">{{ $task->hero_class->emoji() }}</span>
                        @endif
                        <x-points :value="$task->points" size="md" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

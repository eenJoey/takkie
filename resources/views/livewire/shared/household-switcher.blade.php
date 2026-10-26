<div x-data="{ open: false }" class="relative" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <button type="button" x-on:click="open = ! open" :aria-expanded="open"
        class="flex w-full items-center justify-between gap-2 rounded-xl bg-muted px-3 py-2 text-sm font-semibold transition hover:bg-accent/40">
        <span class="truncate">{{ $current?->name ?? 'Geen huishouden' }}</span>
        <x-icon name="chevron-down" class="size-4 opacity-60" />
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute left-0 z-50 mt-1 w-56 rounded-xl border border-border bg-card p-1 shadow-soft">
        @foreach ($this->households as $household)
            <button type="button" wire:key="hh-{{ $household->id }}" wire:click="switchTo({{ $household->id }})"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm hover:bg-secondary">
                <span class="flex-1 truncate">{{ $household->name }}</span>
                @if ($current?->id === $household->id)
                    <x-icon name="check" class="size-4 text-primary" />
                @endif
            </button>
        @endforeach
        <div class="my-1 h-px bg-border"></div>
        <a href="{{ route('household') }}" wire:navigate class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-secondary">
            <x-icon name="plus" />
            Beheren / nieuw
        </a>
    </div>
</div>

{{--
    Feestje bij een level-up of prestige in "Mijn held".
    Bron: $this->dispatch('hero-level-up', emoji: '🍳', heading: '...', text: '...', big: false) vanuit Livewire.
--}}
<div
    x-data="{ show: false, data: {} }"
    x-on:hero-level-up.window="data = $event.detail; show = true"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/40 p-4"
    x-on:click.self="show = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="hero-celebration-heading"
>
    <div x-show="show" x-transition.scale.90 class="w-full max-w-sm rounded-3xl bg-card p-8 text-center shadow-card">
        <div class="mx-auto flex size-24 items-center justify-center rounded-full bg-primary/15 text-6xl"
            :class="data.big && 'ring-4 ring-warning/60'">
            <span x-text="data.emoji"></span>
        </div>
        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-primary" x-text="data.big ? 'Wauw!' : 'Level omhoog!'"></p>
        <h2 id="hero-celebration-heading" class="mt-1 text-2xl font-extrabold" x-text="data.heading"></h2>
        <p class="mt-2 text-sm text-muted-foreground" x-text="data.text"></p>
        <div class="mt-6 flex justify-center gap-2">
            <x-button variant="outline" class="rounded-full" :href="route('hero')" wire:navigate x-on:click="show = false">Mijn held</x-button>
            <x-button class="rounded-full" x-on:click="show = false">Top!</x-button>
        </div>
    </div>
</div>

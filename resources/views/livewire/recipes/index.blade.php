<div>
    <x-page-header title="Receptenboek" subtitle="Kies een recept of maak een nieuwe.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" :href="route('weekmenu')" wire:navigate>
                <x-icon name="calendar" />
                Weekmenu
            </x-button>
            <x-button variant="secondary" size="sm" :href="route('recipes.discover')" wire:navigate>Ontdekken</x-button>
            <x-button size="sm" :href="route('recipes.create')" wire:navigate>
                <x-icon name="plus" />
                Nieuw recept
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" type="search" placeholder="Zoek recepten..." aria-label="Zoek recepten" />
    </x-card>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
        @forelse ($recipes as $recipe)
            <x-card wire:key="recipe-{{ $recipe->id }}" class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold text-muted-foreground">Recept</span>
                    @if ($recipe->hasSourceUpdate())
                        <span class="rounded-full bg-primary px-2.5 py-1 text-xs font-semibold text-primary-foreground">Nieuwe versie</span>
                    @endif
                    @if ($recipe->isCopy())
                        <span class="rounded-full bg-accent/40 px-2.5 py-1 text-xs font-semibold text-foreground">Kopie</span>
                    @endif
                    @if ($recipe->isPublic())
                        <span class="rounded-full bg-success/15 px-2.5 py-1 text-xs font-semibold text-foreground">Openbaar</span>
                    @endif
                </div>
                <h3 class="break-words text-lg font-bold">{{ $recipe->title }}</h3>
                <div class="mt-auto flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ $recipe->ingredients_count }} items</span>
                    <div class="flex items-center gap-2">
                        <x-badge>{{ $recipe->remaining_count }} open</x-badge>
                        <x-button size="sm" :href="route('recipes.show', $recipe)" wire:navigate>Open</x-button>
                    </div>
                </div>
            </x-card>
        @empty
            <x-empty-state emoji="📖" title="Nog geen recepten gevonden." class="sm:col-span-2 lg:col-span-3 2xl:col-span-4">
                <p class="mt-2 text-sm">
                    <a href="{{ route('recipes.discover') }}" wire:navigate class="font-semibold underline hover:text-primary">Ontdek openbare recepten</a>
                </p>
            </x-empty-state>
        @endforelse
    </div>
</div>

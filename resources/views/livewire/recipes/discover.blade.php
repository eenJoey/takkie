<div>
    <x-page-header title="Recepten ontdekken" subtitle="Openbare recepten van andere huishoudens. Zoek op naam, ingrediënt of tag." />

    <x-card class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" type="search" placeholder="Zoek op recept, ingrediënt of tag..." aria-label="Zoek recepten" />
        @if ($tag !== '')
            <div class="mt-3 flex items-center gap-2 text-sm">
                <span class="text-muted-foreground">Tag:</span>
                <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold">#{{ $tag }}</span>
                <button type="button" wire:click="clearTag" class="text-xs font-semibold text-muted-foreground hover:text-primary">Wissen</button>
            </div>
        @endif
    </x-card>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($recipes as $recipe)
            <x-card wire:key="public-recipe-{{ $recipe->id }}" class="flex flex-col gap-3">
                <h3 class="break-words text-lg font-bold">{{ $recipe->title }}</h3>
                @if ($recipe->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($recipe->tags as $recipeTag)
                            <button type="button" wire:click="$set('tag', @js($recipeTag->slug))"
                                class="rounded-full bg-secondary px-2 py-0.5 text-xs font-semibold text-muted-foreground hover:bg-accent/60">#{{ $recipeTag->name }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="mt-auto flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ $recipe->ingredients_count }} items</span>
                    <x-button size="sm" :href="route('recipes.show', $recipe)" wire:navigate>Bekijk</x-button>
                </div>
            </x-card>
        @empty
            <x-empty-state emoji="🔎" title="Geen openbare recepten gevonden." description="Probeer een ander zoekwoord." class="sm:col-span-2 lg:col-span-3" />
        @endforelse
    </div>

    <div class="mt-6">
        {{ $recipes->links() }}
    </div>
</div>

<div @if ($isMember) wire:poll.5s.visible @endif>
    <x-page-header :title="$recipe->title">
        <x-slot:actions>
            @if ($isMember)
                <x-button size="sm" variant="secondary" :href="route('recipes.index')" wire:navigate>
                    <x-icon name="arrow-left" />
                    Terug
                </x-button>
            @else
                <x-button size="sm" variant="secondary" :href="route('recipes.discover')" wire:navigate>
                    <x-icon name="arrow-left" />
                    Ontdekken
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($isMember && $recipe->isCopy())
        <div class="mb-4 flex items-start gap-3 rounded-2xl bg-secondary px-4 py-3 text-sm">
            <x-icon name="lock" class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
            <p>
                <span class="font-semibold">Kopie van een openbaar recept</span> — alleen-lezen, afvinken kan wel.
                @if ($source?->isPublic())
                    <a href="{{ route('recipes.show', $source->slug) }}" class="font-semibold underline hover:text-primary">Bekijk het origineel</a>
                @elseif ($source === null)
                    <span class="text-muted-foreground">Het originele recept is verwijderd; deze kopie blijft bewaard.</span>
                @else
                    <span class="text-muted-foreground">Het originele recept is niet meer openbaar; deze kopie blijft bewaard.</span>
                @endif
            </p>
        </div>
    @endif

    @if ($hasUpdate)
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border-2 border-primary bg-primary/10 px-4 py-3 text-sm sm:flex-row sm:items-center">
            <x-icon name="bell" class="hidden size-5 shrink-0 sm:block" />
            <p class="flex-1">
                <span class="font-semibold">Het originele recept is aangepast.</span>
                Wil je de nieuwe versie overnemen? Je huidige kopie wordt dan vervangen.
                <a href="{{ route('recipes.show', $source->slug) }}" class="font-semibold text-link underline">Bekijk de nieuwe versie</a>
            </p>
            <div class="flex shrink-0 gap-2">
                <x-button size="sm" wire:click="acceptSourceUpdate" wire:loading.attr="disabled">Overnemen</x-button>
                <x-button size="sm" variant="secondary" wire:click="dismissSourceUpdate" wire:loading.attr="disabled">Niet overnemen</x-button>
            </div>
        </div>
    @endif

    @if ($existingCopy)
        <div class="mb-4">
            <x-button size="sm" variant="secondary" :href="route('recipes.show', $existingCopy)">
                <x-icon name="check" />
                Staat al in je recepten
            </x-button>
        </div>
    @elseif ($canAdd)
        <div class="mb-4">
            <x-button size="sm" wire:click="addToMyRecipes" wire:loading.attr="disabled" wire:target="addToMyRecipes">
                <x-icon name="plus" />
                Toevoegen aan mijn recepten
            </x-button>
        </div>
    @endif

    @if ($tags->isNotEmpty() || $recipe->isPublic())
        <div class="-mt-2 mb-4 flex flex-wrap gap-2">
            @if ($recipe->isPublic() && $isMember)
                <span class="rounded-full bg-success/15 px-2.5 py-1 text-xs font-semibold">Openbaar</span>
            @endif
            @foreach ($tags as $tag)
                <a href="{{ route('recipes.discover', ['tag' => $tag->slug]) }}" wire:navigate
                    class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold text-muted-foreground hover:bg-accent/60">#{{ $tag->name }}</a>
            @endforeach
        </div>
    @endif

    <div class="grid items-start gap-4 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-3 text-base font-bold">Boodschappenlijstje</h3>
            <div class="flex flex-col gap-2">
                @forelse ($ingredients as $ingredient)
                    <div wire:key="ingredient-{{ $ingredient->id }}" class="flex items-center gap-2.5 rounded-xl border border-item-border bg-item px-3 py-2.5">
                        @if ($isMember)
                            <x-check :done="$ingredient->is_checked" wire:click="toggleItem({{ $ingredient->id }})" />
                            <span @class(['flex-1 break-words text-[15px]', 'text-muted-foreground line-through' => $ingredient->is_checked])>{{ $ingredient->text }}</span>
                        @else
                            <span class="size-2 shrink-0 rounded-full bg-primary"></span>
                            <span class="flex-1 break-words text-[15px]">{{ $ingredient->text }}</span>
                        @endif
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-muted-foreground">Nog geen items toegevoegd.</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h3 class="mb-3 text-base font-bold">Instructies</h3>
            <div class="text-[15px] leading-relaxed">
                @if ($recipe->instructions)
                    {!! nl2br(e($recipe->instructions)) !!}
                @else
                    <span class="text-muted-foreground">Nog geen instructies toegevoegd.</span>
                @endif
            </div>
        </x-card>
    </div>

    @if ($isMember)
        <div class="mt-4 flex justify-end gap-2">
            @can('update', $recipe)
                <x-button size="sm" :href="route('recipes.edit', $recipe)" wire:navigate>
                    <x-icon name="pencil" />
                    Bewerken
                </x-button>
            @endcan
            @can('delete', $recipe)
                <x-button size="sm" variant="outline" wire:click="deleteCopy" wire:confirm="Deze kopie uit je recepten verwijderen?">
                    <x-icon name="trash" />
                    Verwijderen
                </x-button>
            @endcan
            <x-button size="sm" variant="secondary" wire:click="resetItems">Reset lijst</x-button>
        </div>
    @endif
</div>

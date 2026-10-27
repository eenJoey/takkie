<div>
    <x-page-header :title="$recipe ? 'Recept bewerken' : 'Nieuw recept'" :subtitle="$recipe?->title ?? 'Maak een recept met lijstje en instructies.'">
        <x-slot:actions>
            <x-button size="sm" variant="secondary" :href="$recipe ? route('recipes.show', $recipe) : route('recipes.index')" wire:navigate>
                <x-icon name="arrow-left" />
                Terug
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @unless ($recipe)
        {{-- recept uit een link: vult het formulier hieronder --}}
        <x-card class="mb-4">
            <form wire:submit="importFromUrl" class="space-y-2">
                <x-label for="import-url">Recept uit een link</x-label>
                <div class="flex gap-2">
                    <x-input id="import-url" type="url" wire:model="importUrl" placeholder="https://www.ah.nl/allerhande/recept/…" maxlength="2000" />
                    <x-button type="submit" class="shrink-0" wire:loading.attr="disabled" wire:target="importFromUrl">
                        <x-icon name="link" />
                        <span wire:loading.remove wire:target="importFromUrl">Ophalen</span>
                        <span wire:loading wire:target="importFromUrl">Bezig…</span>
                    </x-button>
                </div>
                <p class="text-xs text-muted-foreground">Werkt op de meeste receptensites. Je kunt alles daarna nog aanpassen.</p>
            </form>
        </x-card>
    @endunless

    <form wire:submit="save">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-card class="space-y-4">
                <div>
                    <x-label for="title">Naam</x-label>
                    <x-input id="title" wire:model="title" required maxlength="255" />
                    @error('title') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-label for="servings">Aantal personen</x-label>
                    <x-input id="servings" type="number" inputmode="numeric" min="1" max="50" wire:model="servings" placeholder="bijv. 4" class="max-w-32" />
                    @error('servings') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-muted-foreground">Voor hoeveel personen de hoeveelheden zijn. Dan kun je het recept omrekenen.</p>
                </div>

                <div>
                    <x-label for="items">Boodschappenlijstje</x-label>
                    <div class="flex gap-2">
                        <x-input id="items" wire:model="newItemText" wire:keydown.enter.prevent="addItem" placeholder="Voeg een item toe..." maxlength="255" />
                        <x-button wire:click="addItem" class="min-w-11">
                            <x-icon name="plus" class="size-5" />
                            <span class="sr-only">Item toevoegen</span>
                        </x-button>
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        @forelse ($items as $index => $item)
                            <div wire:key="item-{{ $index }}-{{ md5($item) }}" class="flex items-center gap-2.5 rounded-xl border border-item-border bg-item px-3 py-2.5">
                                <span class="flex-1 break-words text-[15px]">{{ $item }}</span>
                                <x-button variant="danger-icon" wire:click="removeItem({{ $index }})">
                                    <x-icon name="trash" />
                                    <span class="sr-only">Item verwijderen</span>
                                </x-button>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-muted-foreground">Nog geen items toegevoegd.</p>
                        @endforelse
                    </div>
                </div>
            </x-card>

            <x-card class="space-y-4">
                <div>
                    <x-label for="instructions">Instructies</x-label>
                    <x-textarea id="instructions" wire:model="instructions" rows="8" placeholder="Stap-voor-stap uitleg..." />
                </div>

                <div>
                    <x-label for="tags">Tags</x-label>
                    <x-input id="tags" wire:model="tags" placeholder="bijv. vegetarisch, pasta, snel" maxlength="500" />
                    <p class="mt-1 text-xs text-muted-foreground">Scheid tags met een komma. Ze helpen bij het zoeken.</p>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-muted/50 p-3">
                    <input type="checkbox" wire:model="isPublic" class="mt-1 size-4 accent-primary">
                    <span>
                        <span class="block text-sm font-semibold">Openbaar recept</span>
                        <span class="block text-xs text-muted-foreground">Iedereen kan dit recept vinden en bekijken via Ontdekken. Alleen jouw huishouden kan het bewerken.</span>
                    </span>
                </label>
            </x-card>
        </div>

        <div class="mt-4 flex justify-end">
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">Opslaan</x-button>
        </div>
    </form>
</div>

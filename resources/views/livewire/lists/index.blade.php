<div>
    <x-page-header title="Lijstjes" subtitle="Maak een nieuwe lijst of open een bestaande." />

    <div class="mb-4 grid gap-4 md:grid-cols-2">
    <x-card>
        <x-input wire:model.live.debounce.300ms="search" type="search" placeholder="Zoek lijstjes..." aria-label="Zoek lijstjes" />
    </x-card>

    <x-card>
        <form wire:submit="create">
            <label for="new-list-name" class="mb-2 block text-sm font-semibold text-muted-foreground">Nieuwe lijst</label>
            <div class="flex gap-2">
                <x-input id="new-list-name" wire:model="name" required maxlength="255" />
                <x-button type="submit" class="min-w-11">
                    <x-icon name="plus" class="size-5" />
                    <span class="sr-only">Lijst toevoegen</span>
                </x-button>
            </div>
            @error('name')
                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
            @enderror
        </form>
    </x-card>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($lists as $list)
            <x-card wire:key="list-{{ $list->id }}" class="flex flex-col justify-center">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                    <h3 class="min-w-0 flex-1 basis-40 break-words text-lg font-bold">{{ $list->name }}</h3>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($list->isChecklist())
                            <x-badge>{{ $list->categories_count }} {{ $list->categories_count === 1 ? 'categorie' : 'categorieën' }}</x-badge>
                        @else
                            <x-badge>Taken</x-badge>
                        @endif
                        <x-button size="sm" :href="route('lists.show', $list)" wire:navigate>Open</x-button>
                        @can('delete', $list)
                            <x-button size="sm" variant="secondary" wire:click="delete({{ $list->id }})">Verwijder</x-button>
                        @endcan
                    </div>
                </div>
            </x-card>
        @empty
            <x-empty-state emoji="📝" title="Nog geen lijstjes." class="sm:col-span-2 lg:col-span-3" />
        @endforelse
    </div>
</div>

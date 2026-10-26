<div @if ($list->isChecklist()) wire:poll.5s.visible @endif>
    <x-page-header :title="$list->name" :subtitle="$list->isChecklist() ? 'Checklist' : 'Takenlijst'">
        <x-slot:actions>
            <x-button size="sm" variant="secondary" :href="route('lists.index')" wire:navigate>
                <x-icon name="arrow-left" />
                Terug
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if (! $list->isChecklist())
        {{-- De takenweergave (punten, herhaling) komt in M4 --}}
        <x-empty-state emoji="🌿" title="Takenlijst" description="De weergave voor taken met punten volgt in een volgende stap." />
    @else
        <div class="space-y-4">
            @foreach ($categories as $category)
                @php $open = $category->tasks->whereNull('completed_at')->count(); @endphp
                <x-card wire:key="category-{{ $category->id }}">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="size-3 shrink-0 rounded-full {{ $category->color->dotClass() }}"></span>
                            <h2 class="truncate text-lg font-bold">{{ $category->name }}</h2>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-badge>{{ $open }} open</x-badge>
                            <x-button variant="danger-icon" wire:click="deleteCategory({{ $category->id }})">
                                <x-icon name="trash" />
                                <span class="sr-only">Categorie verwijderen</span>
                            </x-button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        @foreach ($category->tasks as $task)
                            <div wire:key="task-{{ $task->id }}" class="flex items-center gap-2.5 rounded-xl border border-item-border bg-item px-3 py-2.5">
                                <x-check :done="$task->isCompleted()" wire:click="toggleItem({{ $task->id }})" />
                                <span @class(['flex-1 break-words text-[15px]', 'text-muted-foreground line-through' => $task->isCompleted()])>
                                    {{ $task->title }}
                                </span>
                                <x-button variant="danger-icon" wire:click="deleteItem({{ $task->id }})">
                                    <x-icon name="trash" />
                                    <span class="sr-only">Item verwijderen</span>
                                </x-button>
                            </div>
                        @endforeach

                        @if ($activeCategory === $category->id)
                            <form wire:submit="addItem({{ $category->id }})" class="flex gap-2">
                                <x-input wire:model="newItemText" placeholder="Nieuw item..." maxlength="255" autofocus />
                                <x-button type="submit" class="min-w-11">
                                    <x-icon name="plus" class="size-5" />
                                    <span class="sr-only">Item toevoegen</span>
                                </x-button>
                            </form>
                        @else
                            <x-button variant="ghost-dashed" wire:click="$set('activeCategory', {{ $category->id }})">
                                <x-icon name="plus" />
                                Item toevoegen
                            </x-button>
                        @endif
                    </div>
                </x-card>
            @endforeach

            @if ($isAddingCategory)
                <x-card>
                    <form wire:submit="addCategory" class="flex gap-2">
                        <x-input wire:model="newCategoryName" placeholder="Naam categorie..." maxlength="255" autofocus />
                        <x-button type="submit" class="min-w-11">
                            <x-icon name="plus" class="size-5" />
                            <span class="sr-only">Categorie toevoegen</span>
                        </x-button>
                    </form>
                </x-card>
            @else
                <x-button variant="ghost-dashed" size="lg" wire:click="$set('isAddingCategory', true)">
                    <x-icon name="plus" class="size-5" />
                    Nieuwe categorie
                </x-button>
            @endif
        </div>
    @endif
</div>

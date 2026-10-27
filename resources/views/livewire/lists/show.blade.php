<div @if ($list->isChecklist()) wire:poll.3s.visible @endif>
    @if ($list->isChecklist())
        <x-page-header :title="$list->name" subtitle="Checklist">
            <x-slot:actions>
                <x-button size="sm" variant="secondary" :href="route('lists.index')" wire:navigate>
                    <x-icon name="arrow-left" />
                    Terug
                </x-button>
            </x-slot:actions>
        </x-page-header>
    @else
        <x-page-header title="Taken" />
    @endif

    @if (! $list->isChecklist())
        <livewire:tasks.board :list="$list" :key="'board-'.$list->id" />
    @else
        <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
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
                            @php
                                // net (laatste minuut) afgevinkt door iemand anders: even opvallen, zodat je het niet dubbel koopt
                                $byOther = $task->isCompleted() && $task->completer && $task->completed_by !== auth()->id();
                                $justNow = $byOther && $task->completed_at->gt(now()->subMinute());
                            @endphp
                            <div wire:key="task-{{ $task->id }}" @class([
                                'flex items-center gap-2.5 rounded-xl border bg-item px-3 py-2.5 transition',
                                'border-primary ring-2 ring-primary/30' => $justNow,
                                'border-item-border' => ! $justNow,
                            ])>
                                <x-check :done="$task->isCompleted()" wire:click="toggleItem({{ $task->id }})" />
                                <span @class(['flex-1 break-words text-[15px]', 'text-muted-foreground line-through' => $task->isCompleted()])>
                                    {{ $task->title }}
                                </span>
                                @if ($task->isCompleted() && $task->completer)
                                    <span class="flex shrink-0 items-center gap-1" title="Afgevinkt door {{ $task->completer->name }}">
                                        @if ($justNow)
                                            <span class="text-xs font-semibold text-link">net</span>
                                        @endif
                                        <x-avatar :user="$task->completer" class="size-6 text-[10px]" />
                                        <span class="sr-only">Afgevinkt door {{ $task->completer->name }}</span>
                                    </span>
                                @endif
                                <x-button variant="danger-icon" wire:click="deleteItem({{ $task->id }})">
                                    <x-icon name="trash" />
                                    <span class="sr-only">Item verwijderen</span>
                                </x-button>
                            </div>
                        @endforeach

                        @if ($activeCategory === $category->id)
                            <form wire:submit="addItem({{ $category->id }})" class="flex gap-2">
                                <x-input wire:model="newItemText" placeholder="Bijv. melk, brood, kaas" maxlength="2000" autofocus />
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
                <x-card class="md:col-span-2">
                    <form wire:submit="addCategory" class="flex gap-2">
                        <x-input wire:model="newCategoryName" placeholder="Naam categorie..." maxlength="255" autofocus />
                        <x-button type="submit" class="min-w-11">
                            <x-icon name="plus" class="size-5" />
                            <span class="sr-only">Categorie toevoegen</span>
                        </x-button>
                    </form>
                </x-card>
            @else
                <x-button variant="ghost-dashed" size="lg" class="md:col-span-2" wire:click="$set('isAddingCategory', true)">
                    <x-icon name="plus" class="size-5" />
                    Nieuwe categorie
                </x-button>
            @endif
        </div>
    @endif
</div>

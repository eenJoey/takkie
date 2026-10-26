<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">Vink af en verdien punten ✨</p>
        <x-button class="rounded-full shadow-card" wire:click="openNew">
            <x-icon name="plus" />
            Nieuw
        </x-button>
    </div>

    @if ($this->groups->isEmpty())
        <x-empty-state emoji="🌿" title="Nog geen taken" description="Voeg je eerste taak toe om te beginnen." />
    @else
        <div class="space-y-8">
            @foreach ($this->groups as $group)
                <section wire:key="group-{{ $group['key']->value }}">
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-bold">
                        <span>{{ $group['emoji'] }}</span> {{ $group['label'] }}
                        <span class="text-sm font-semibold text-muted-foreground">({{ $group['items']->count() }})</span>
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['items'] as $task)
                            @php $overdue = $task->isOverdue($today); @endphp
                            <div wire:key="task-{{ $task->id }}" @class([
                                'flex gap-3 rounded-2xl bg-card p-4 shadow-card',
                                'ring-2 ring-destructive/40' => $overdue,
                            ])>
                                <button type="button" wire:click="complete({{ $task->id }})" wire:loading.attr="disabled" aria-label="Afvinken"
                                    class="group flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-border transition hover:border-success hover:bg-success/10">
                                    <x-icon name="check" class="size-5 text-transparent group-hover:text-success" />
                                </button>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="font-bold leading-tight break-words">{{ $task->title }}</h3>
                                        <x-points :value="$task->points" />
                                    </div>
                                    @if ($task->description)
                                        <p class="mt-0.5 line-clamp-2 text-sm text-muted-foreground">{{ $task->description }}</p>
                                    @endif
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                        @if ($task->due_date)
                                            <span @class(['font-semibold text-destructive' => $overdue])>
                                                {{ $overdue ? 'Te laat: ' : '' }}{{ $task->dueLabel() }}
                                            </span>
                                            <span>•</span>
                                        @endif
                                        <span>{{ $task->recurrenceLabel() }}</span>
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-1">
                                            <x-icon name="user" class="size-3" />
                                            {{ $task->assignee?->name ?? 'Iedereen' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <button type="button" wire:click="openEdit({{ $task->id }})" class="p-1 text-muted-foreground hover:text-primary" aria-label="Aanpassen">
                                        <x-icon name="pencil" />
                                    </button>
                                    <button type="button" wire:click="delete({{ $task->id }})" class="p-1 text-muted-foreground hover:text-destructive" aria-label="Verwijderen">
                                        <x-icon name="trash" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <x-dialog name="task-form" :title="$form->taskId ? 'Taak aanpassen' : 'Nieuwe taak'">
        <form wire:submit="save" class="space-y-3">
            <div>
                <x-label for="task-title">Titel</x-label>
                <x-input id="task-title" wire:model="form.title" required maxlength="120" />
                @error('form.title') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-label for="task-description">Beschrijving (optioneel)</x-label>
                <x-textarea id="task-description" wire:model="form.description" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="task-points">Punten</x-label>
                    <x-input id="task-points" type="number" min="0" wire:model="form.points" />
                    @error('form.points') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-label for="task-date">Datum</x-label>
                    <x-input id="task-date" type="date" wire:model="form.dueDate" required />
                    @error('form.dueDate') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="task-recurrence">Herhaling</x-label>
                    <x-select id="task-recurrence" wire:model.live="form.recurrenceType">
                        <option value="once">Eenmalig</option>
                        <option value="daily">Dagelijks</option>
                        <option value="weekly">Wekelijks</option>
                        <option value="monthly">Maandelijks</option>
                    </x-select>
                </div>
                @if ($form->recurrenceType !== 'once')
                    <div>
                        <x-label for="task-interval">Elke X {{ ['daily' => 'dagen', 'weekly' => 'weken', 'monthly' => 'maanden'][$form->recurrenceType] ?? '' }}</x-label>
                        <x-input id="task-interval" type="number" min="1" wire:model="form.recurrenceInterval" />
                        @error('form.recurrenceInterval') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
            <div>
                <x-label for="task-assignee">Toegewezen aan</x-label>
                <x-select id="task-assignee" wire:model="form.assignedTo">
                    <option value="">Iedereen</option>
                    @foreach ($this->members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-button type="submit" class="w-full rounded-full" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $form->taskId ? 'Wijzigingen opslaan' : 'Taak aanmaken' }}</span>
                <span wire:loading wire:target="save">Opslaan…</span>
            </x-button>
        </form>
    </x-dialog>
</div>

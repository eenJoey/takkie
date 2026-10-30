<?php

namespace App\Livewire\Tasks;

use App\Actions\CompleteTask;
use App\Enums\RecurrenceType;
use App\Exceptions\TaskException;
use App\Livewire\Forms\TaskForm;
use App\Models\Task;
use App\Models\TodoList;
use App\Support\CurrentHousehold;
use App\Support\Today;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Weergave van de vaste lijst "Huishoudtaken" (type tasks), port van vinkie lovable /tasks:
 * gegroepeerd per herhaling, afvinken met punten, aanpassen en verwijderen.
 */
class Board extends Component
{
    #[Locked]
    public TodoList $list;

    public TaskForm $form;

    /** Volgorde en emoji's zoals vinkie */
    public const GROUPS = [
        ['key' => RecurrenceType::Daily, 'label' => 'Dagelijks', 'emoji' => '☀️'],
        ['key' => RecurrenceType::Weekly, 'label' => 'Wekelijks', 'emoji' => '📅'],
        ['key' => RecurrenceType::Monthly, 'label' => 'Maandelijks', 'emoji' => '🗓️'],
        ['key' => RecurrenceType::Once, 'label' => 'Eenmalig', 'emoji' => '📌'],
    ];

    #[Computed]
    public function groups(): Collection
    {
        // afgeronde eenmalige taken zijn in vinkie gearchiveerd en dus verborgen (Q13)
        $tasks = $this->list->tasks()->whereNull('completed_at')->with('assignee')->get();

        return collect(self::GROUPS)
            ->map(fn (array $group) => [
                ...$group,
                'items' => $tasks
                    ->filter(fn (Task $task) => $task->recurrence_type === $group['key'])
                    ->sortBy([
                        ['recurrence_interval', 'asc'],
                        fn (Task $a, Task $b) => ($a->due_date?->toDateString() ?? '9999') <=> ($b->due_date?->toDateString() ?? '9999'),
                    ])
                    ->values(),
            ])
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values();
    }

    #[Computed]
    public function members(): Collection
    {
        return app(CurrentHousehold::class)->get()->users()->orderBy('name')->get();
    }

    public function openNew(): void
    {
        $this->form->startNew();
        $this->dispatch('open-dialog', name: 'task-form');
    }

    public function openEdit(int $taskId): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('update', $task);

        $this->form->edit($task);
        $this->dispatch('open-dialog', name: 'task-form');
    }

    public function updatedFormTitle(): void
    {
        $this->form->suggestClass();
    }

    public function save(): void
    {
        $data = $this->form->payload();

        if ($this->form->taskId) {
            $task = $this->list->tasks()->findOrFail($this->form->taskId);
            $this->authorize('update', $task);
            $task->update($data);
            $message = 'Taak bijgewerkt';
        } else {
            $this->authorize('update', $this->list);
            $this->list->tasks()->create([...$data, 'created_by' => auth()->id()]);
            $message = 'Taak aangemaakt';
        }

        $this->form->reset();
        unset($this->groups);
        $this->dispatch('close-dialog');
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function complete(int $taskId, CompleteTask $completeTask): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('complete', $task);

        try {
            $completion = $completeTask->handle(auth()->user(), $task);
        } catch (TaskException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->groups);
        $this->dispatch('task-completed');
        $this->dispatch('points-updated');
        $gain = $completeTask->xpGain;
        $this->dispatch('toast', type: 'success', message: "🎉 +{$completion->points_awarded} punten!".($gain ? ' · '.$gain->toast() : ''));

        if ($gain?->leveledUp()) {
            $this->dispatch('hero-level-up', ...$gain->celebration());
        }
    }

    public function delete(int $taskId): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('delete', $task);

        $task->delete();
        unset($this->groups);
    }

    public function render(): View
    {
        return view('livewire.tasks.board', [
            'today' => Today::date(),
        ]);
    }
}

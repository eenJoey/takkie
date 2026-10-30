<?php

namespace App\Livewire\Forms;

use App\Enums\HeroClass;
use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Support\CurrentHousehold;
use App\Support\Today;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** Formulier "Nieuwe taak" / "Taak aanpassen" (vinkie TaskForm). */
class TaskForm extends Form
{
    public ?int $taskId = null;

    public string $title = '';

    public string $description = '';

    public int $points = 10;

    public string $dueDate = '';

    public string $recurrenceType = 'once';

    public int $recurrenceInterval = 1;

    /** App\Enums\HeroClass; '' = geen klasse (geen XP) */
    public string $heroClass = '';

    /** '' = Iedereen */
    public string $assignedTo = '';

    public function startNew(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->dueDate = Today::iso();
    }

    public function edit(Task $task): void
    {
        $this->resetValidation();
        $this->taskId = $task->id;
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->points = $task->points;
        $this->heroClass = $task->hero_class?->value ?? '';
        $this->dueDate = $task->due_date?->toDateString() ?? Today::iso();
        $this->recurrenceType = $task->recurrence_type->value;
        $this->recurrenceInterval = $task->recurrence_interval;
        $this->assignedTo = (string) ($task->assigned_to ?? '');
    }

    /** Nieuwe taak zonder gekozen klasse: stel er een voor op basis van de titel. */
    public function suggestClass(): void
    {
        if ($this->taskId === null && $this->heroClass === '') {
            $this->heroClass = HeroClass::guess($this->title)?->value ?? '';
        }
    }

    protected function rules(): array
    {
        $memberIds = app(CurrentHousehold::class)->get()?->users()->pluck('users.id')->all() ?? [];

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:0', 'max:100000'],
            'heroClass' => ['nullable', Rule::enum(HeroClass::class)],
            'dueDate' => ['required', 'date_format:Y-m-d'],
            'recurrenceType' => ['required', Rule::enum(RecurrenceType::class)],
            'recurrenceInterval' => ['required', 'integer', 'min:1', 'max:365'],
            'assignedTo' => ['nullable', Rule::in(array_map('strval', $memberIds))],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'titel',
            'points' => 'punten',
            'heroClass' => 'klasse',
            'dueDate' => 'datum',
            'recurrenceInterval' => 'interval',
            'assignedTo' => 'toegewezen aan',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $this->validate();

        return [
            'title' => trim($this->title),
            'description' => trim($this->description) ?: null,
            'points' => $this->points,
            'hero_class' => $this->heroClass === '' ? null : $this->heroClass,
            'due_date' => $this->dueDate,
            'recurrence_type' => $this->recurrenceType,
            'recurrence_interval' => $this->recurrenceType === 'once' ? 1 : $this->recurrenceInterval,
            'assigned_to' => $this->assignedTo === '' ? null : (int) $this->assignedTo,
        ];
    }
}

<?php

namespace App\Livewire;

use App\Actions\CompleteTask;
use App\Enums\ListType;
use App\Exceptions\TaskException;
use App\Models\Task;
use App\Models\TodoList;
use App\Support\Today as TodayDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /dashboard "Vandaag" (vinkie lovable Home): taken per dag met dagnavigatie.
 * Vandaag/verleden: taken met vervaldatum ≤ die dag. Toekomst: geplande herhalingen + nog openstaande achterstallige taken.
 * Afvinken kan niet op toekomstige dagen.
 */
#[Title('Vandaag')]
class Today extends Component
{
    public int $offset = 0;

    public function previousDay(): void
    {
        $this->offset--;
    }

    public function nextDay(): void
    {
        $this->offset++;
    }

    public function backToToday(): void
    {
        $this->offset = 0;
    }

    #[Computed]
    public function today(): Carbon
    {
        return TodayDate::date();
    }

    #[Computed]
    public function shown(): Carbon
    {
        return $this->today->copy()->addDays($this->offset);
    }

    #[Computed]
    public function tasks(): Collection
    {
        $today = $this->today;
        $shown = $this->shown;

        return Task::query()
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereIn('list_id', TodoList::where('type', ListType::Tasks)->select('id'))
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Task $task) => $shown->lte($today)
                ? $task->due_date->lte($shown)
                : $task->occursOn($shown) || $task->due_date->lt($today))
            ->values();
    }

    #[Computed]
    public function tasksList(): ?TodoList
    {
        return TodoList::where('type', ListType::Tasks)->first();
    }

    public function label(): string
    {
        return match ($this->offset) {
            0 => 'Vandaag',
            1 => 'Morgen',
            -1 => 'Gisteren',
            default => $this->shown->locale('nl')->translatedFormat('l j F'),
        };
    }

    public function greeting(): string
    {
        $hour = TodayDate::now()->hour;

        return match (true) {
            $hour < 6 => 'Goedenacht',
            $hour < 12 => 'Goedemorgen',
            $hour < 18 => 'Goedemiddag',
            default => 'Goedenavond',
        };
    }

    public function complete(int $taskId, CompleteTask $completeTask): void
    {
        if ($this->offset > 0) {
            return;
        }

        $task = Task::findOrFail($taskId);
        $this->authorize('complete', $task);

        try {
            $completion = $completeTask->handle(auth()->user(), $task);
        } catch (TaskException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->tasks);
        $this->dispatch('task-completed');
        $this->dispatch('points-updated');
        $this->dispatch('toast', type: 'success', message: "🎉 +{$completion->points_awarded} punten!");
    }

    public function render(): View
    {
        return view('livewire.today');
    }
}

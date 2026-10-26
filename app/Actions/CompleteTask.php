<?php

namespace App\Actions;

use App\Enums\ListType;
use App\Enums\RecurrenceType;
use App\Exceptions\TaskException;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\Support\Today;
use Illuminate\Support\Facades\DB;

/**
 * Taak uit de takenlijst afvinken (← vinkie-trigger handle_task_completion, lovable-versie):
 * - punten komen altijd van de taak, nooit van de client
 * - eenmalig: afgerond en vervaldatum leeg; herhalend: volgende datum = (oude datum of vandaag) + interval
 * - punten bij op het saldo van de afvinker in dit huishouden
 * Niet terug te draaien, zoals in vinkie.
 */
class CompleteTask
{
    public function handle(User $user, Task $task): TaskCompletion
    {
        return DB::transaction(function () use ($user, $task) {
            $task = Task::whereKey($task->id)->lockForUpdate()->first();

            if (! $task || $task->isCompleted()) {
                throw new TaskException('Taak bestaat niet');
            }

            if ($task->list->type !== ListType::Tasks) {
                throw new TaskException('Alleen taken uit de takenlijst leveren punten op');
            }

            $member = DB::table('household_user')
                ->where('household_id', $task->household_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                throw new TaskException('Taak hoort niet bij dit huishouden');
            }

            $completion = TaskCompletion::create([
                'household_id' => $task->household_id,
                'task_id' => $task->id,
                'completed_by' => $user->id,
                'points_awarded' => $task->points,
                'completed_at' => now(),
            ]);

            if ($task->recurrence_type === RecurrenceType::Once) {
                $task->update(['completed_at' => now(), 'due_date' => null]);
            } else {
                $task->update(['due_date' => self::nextDueDate($task)]);
            }

            DB::table('household_user')
                ->where('id', $member->id)
                ->increment('points_balance', $task->points);

            return $completion;
        });
    }

    /** Zelfde rekenregel als de vinkie-trigger; maanden zonder overloop (31 jan + 1 maand = 28/29 feb). */
    public static function nextDueDate(Task $task): string
    {
        $base = ($task->due_date ?? Today::date())->copy();
        $interval = max(1, $task->recurrence_interval);

        $next = match ($task->recurrence_type) {
            RecurrenceType::Daily => $base->addDays($interval),
            RecurrenceType::Weekly => $base->addWeeks($interval),
            RecurrenceType::Monthly => $base->addMonthsNoOverflow($interval),
            RecurrenceType::Once => $base,
        };

        return $next->toDateString();
    }
}

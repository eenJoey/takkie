<?php

namespace App\Actions;

use App\Enums\ListType;
use App\Enums\RecurrenceType;
use App\Exceptions\TaskException;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\Models\XpEvent;
use App\Support\Hero\Levels;
use App\Support\Hero\XpGain;
use App\Support\Today;
use Illuminate\Support\Facades\DB;

/**
 * Taak uit de takenlijst afvinken (← vinkie-trigger handle_task_completion, lovable-versie):
 * - punten komen altijd van de taak, nooit van de client
 * - eenmalig: afgerond en vervaldatum leeg; herhalend: eerstvolgende datum in het schema na vandaag (zie nextDueDate)
 * - punten bij op het saldo van de afvinker in dit huishouden
 * - heeft de taak een klasse: XP in die klasse (punten, minimaal 10), zie $xpGain
 * Niet terug te draaien, zoals in vinkie.
 */
class CompleteTask
{
    /** XP van de laatste handle(), of null als de taak geen klasse heeft. */
    public ?XpGain $xpGain = null;

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

            $this->xpGain = $task->hero_class
                ? app(AwardXp::class)->handle($user, $task->household_id, $task->hero_class, max(Levels::MIN_TASK_XP, $task->points), XpEvent::SOURCE_TASK, [
                    'task_completion_id' => $completion->id,
                    'label' => mb_substr($task->title, 0, 255),
                ])
                : null;

            return $completion;
        });
    }

    /**
     * Volgende datum in het vaste schema (oude datum + k × interval) die na vandaag valt.
     * Te laat afgevinkt? Dan niet alle gemiste keren inhalen: het ritme loopt gewoon door vanaf de ingestelde datum.
     * Maanden zonder overloop: 31 jan + 1 maand = 28 feb.
     */
    public static function nextDueDate(Task $task): string
    {
        $today = Today::date();
        $base = ($task->due_date ?? $today)->copy()->startOfDay();
        $interval = max(1, $task->recurrence_interval);

        if ($task->recurrence_type === RecurrenceType::Once) {
            return $base->toDateString();
        }

        $at = fn (int $steps) => match ($task->recurrence_type) {
            RecurrenceType::Daily => $base->copy()->addDays($steps * $interval),
            RecurrenceType::Weekly => $base->copy()->addWeeks($steps * $interval),
            RecurrenceType::Monthly => $base->copy()->addMonthsNoOverflow($steps * $interval),
        };

        $steps = 1;

        while ($at($steps)->lte($today)) {
            $steps++;
        }

        return $at($steps)->toDateString();
    }
}

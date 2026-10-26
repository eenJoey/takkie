<?php

namespace App\Console\Commands;

use App\Enums\ListType;
use App\Models\NotificationSetting;
use App\Models\Scopes\HouseholdScope;
use App\Models\Task;
use App\Models\TodoList;
use App\Notifications\TaskReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Herinneringen versturen (← vinkie POST /api/public/hooks/send-reminders, nu via de scheduler).
 * Draait elke 15 minuten; stuurt een melding als "nu" 0–15 minuten na een meldingsmoment van de gebruiker valt
 * en er in zijn huishoudens taken openstaan met een datum t/m vandaag (lokale datum van de gebruiker).
 */
#[Signature('reminders:send')]
#[Description('Stuur pushmeldingen voor openstaande huishoudtaken')]
class SendTaskReminders extends Command
{
    public function handle(): int
    {
        $now = now();
        $matched = 0;
        $sent = 0;

        NotificationSetting::query()
            ->where('enabled', true)
            ->with('user')
            ->get()
            ->filter(fn (NotificationSetting $settings) => $settings->user && $settings->matchesSlot($now))
            ->each(function (NotificationSetting $settings) use ($now, &$matched, &$sent) {
                $matched++;
                $user = $settings->user;

                if ($user->pushSubscriptions()->doesntExist()) {
                    return;
                }

                $tasks = $this->openTasks($user->households()->pluck('households.id'), $settings->localDate($now))
                    ->filter(fn (Task $task) => ! $settings->only_assigned
                        || $task->assigned_to === null
                        || $task->assigned_to === $user->id)
                    ->values();

                if ($tasks->isEmpty()) {
                    return;
                }

                $body = $tasks->count() === 1
                    ? $tasks->first()->title
                    : "{$tasks->count()} taken staan open, o.a. {$tasks->first()->title}";

                $user->notify(new TaskReminder($body));
                $sent++;
            });

        $this->info("Herinneringen verstuurd: {$sent} (van {$matched} gebruikers op een meldingsmoment)");

        return self::SUCCESS;
    }

    /** Openstaande taken uit de takenlijsten van deze huishoudens (buiten de huishoud-scope: console). */
    private function openTasks(Collection $householdIds, string $localDate): Collection
    {
        if ($householdIds->isEmpty()) {
            return collect();
        }

        $taskListIds = TodoList::withoutGlobalScope(HouseholdScope::class)
            ->whereIn('household_id', $householdIds)
            ->where('type', ListType::Tasks)
            ->pluck('id');

        return Task::withoutGlobalScope(HouseholdScope::class)
            ->whereIn('list_id', $taskListIds)
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $localDate)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }
}

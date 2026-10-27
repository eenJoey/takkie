<?php

namespace App\Support;

use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Streak: aantal dagen op rij (lokale datum) waarop iemand in dit huishouden minstens één taak afvinkte.
 * Vandaag nog niets gedaan breekt de streak niet: dan telt hij door vanaf gisteren.
 */
class Streak
{
    public static function for(User $user, int $householdId): int
    {
        $timezone = config('app.local_timezone');

        $days = TaskCompletion::withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('completed_by', $user->id)
            ->where('completed_at', '>=', now()->subDays(400))
            ->pluck('completed_at')
            ->map(fn ($at) => Carbon::parse($at)->setTimezone($timezone)->toDateString())
            ->flip();

        $day = Today::now()->startOfDay();

        if (! $days->has($day->toDateString())) {
            $day->subDay();
        }

        $streak = 0;

        while ($days->has($day->toDateString())) {
            $streak++;
            $day->subDay();
        }

        return $streak;
    }
}

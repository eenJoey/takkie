<?php

namespace App\Actions;

use App\Enums\HeroClass;
use App\Models\HeroPrestige;
use App\Models\Household;
use App\Models\User;
use App\Models\XpEvent;
use App\Support\CurrentHousehold;
use App\Support\Hero\Hero;
use App\Support\Hero\Levels;
use App\Support\Hero\XpGain;
use App\Support\Today;

/** XP bijschrijven in een klasse, met de prestige-bonus (+10% per prestige). */
class AwardXp
{
    /** @param  array{task_completion_id?: int, recipe_id?: int|null, label?: string|null}  $extra */
    public function handle(User $user, int $householdId, HeroClass $class, int $base, string $source, array $extra = []): XpGain
    {
        $before = Hero::for($user, $householdId)->get($class);

        $prestige = (int) HeroPrestige::withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('user_id', $user->id)
            ->where('hero_class', $class->value)
            ->value('prestige');

        $amount = (int) round(max(0, $base) * Levels::multiplier($prestige));

        app(CurrentHousehold::class)->runAs(Household::findOrFail($householdId), fn () => XpEvent::create([
            'household_id' => $householdId,
            'user_id' => $user->id,
            'hero_class' => $class,
            'amount' => $amount,
            'source' => $source,
            'date' => Today::iso(),
            'created_at' => now(),
            ...$extra,
        ]));

        return new XpGain($class, $amount, $before, Hero::for($user, $householdId)->get($class));
    }
}

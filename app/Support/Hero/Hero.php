<?php

namespace App\Support\Hero;

use App\Enums\HeroClass;
use App\Models\HeroPrestige;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Collection;

/** "Mijn held": de stand van alle klassen van één persoon in één huishouden. */
final class Hero
{
    /** @param  Collection<string, ClassProgress>  $classes */
    private function __construct(public readonly Collection $classes) {}

    public static function for(User $user, int $householdId): self
    {
        $earned = XpEvent::withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('user_id', $user->id)
            ->groupBy('hero_class')
            ->selectRaw('hero_class, SUM(amount) as total')
            ->pluck('total', 'hero_class');

        $prestiges = HeroPrestige::withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (HeroPrestige $p) => $p->hero_class->value);

        return new self(collect(HeroClass::cases())->mapWithKeys(fn (HeroClass $class) => [
            $class->value => new ClassProgress(
                $class,
                (int) ($earned[$class->value] ?? 0),
                (int) ($prestiges[$class->value]?->prestige ?? 0),
                (int) ($prestiges[$class->value]?->xp_spent ?? 0),
            ),
        ]));
    }

    public function get(HeroClass $class): ClassProgress
    {
        return $this->classes[$class->value];
    }

    public function level(): int
    {
        return $this->classes->sum(fn (ClassProgress $p) => $p->heroLevels());
    }

    /** De klasse waar je het verst in bent; null als je nog nergens XP hebt. */
    public function best(): ?ClassProgress
    {
        $best = $this->classes->sortByDesc(fn (ClassProgress $p) => [$p->heroLevels(), $p->xp])->first();

        return $best && $best->earned > 0 ? $best : null;
    }

    /** Gilde-level van het hele huishouden: alle XP samen, drie keer zo langzaam als één klasse. */
    public static function guildLevel(int $householdId): int
    {
        $total = (int) XpEvent::withoutGlobalScopes()->where('household_id', $householdId)->sum('amount');

        return Levels::levelFor(intdiv($total, 3));
    }
}

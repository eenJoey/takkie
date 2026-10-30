<?php

namespace App\Actions;

use App\Enums\HeroClass;
use App\Exceptions\HeroException;
use App\Models\HeroPrestige;
use App\Models\Household;
use App\Models\User;
use App\Notifications\HeroPrestiged;
use App\Support\CurrentHousehold;
use App\Support\Hero\ClassProgress;
use App\Support\Hero\Hero;
use App\Support\Hero\Levels;
use Illuminate\Support\Facades\DB;

use function Illuminate\Support\defer;

/** Op level 50: terug naar level 1 met een prestige-rang en +10% XP in die klasse. */
class PrestigeHeroClass
{
    public function handle(User $user, Household $household, HeroClass $class): ClassProgress
    {
        $progress = DB::transaction(function () use ($user, $household, $class) {
            DB::table('household_user')
                ->where('household_id', $household->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first() ?? throw new HeroException('Je hoort niet bij dit huishouden');

            if (! Hero::for($user, $household->id)->get($class)->canPrestige()) {
                throw new HeroException('Prestige kan pas op level '.Levels::MAX_LEVEL);
            }

            app(CurrentHousehold::class)->runAs($household, function () use ($user, $household, $class) {
                $row = HeroPrestige::firstOrCreate(
                    ['household_id' => $household->id, 'user_id' => $user->id, 'hero_class' => $class],
                );

                $row->update([
                    'prestige' => $row->prestige + 1,
                    'xp_spent' => $row->xp_spent + Levels::prestigeCost(),
                ]);
            });

            return Hero::for($user, $household->id)->get($class);
        });

        // pushmeldingen pas na het antwoord, zodat de knop niet wacht op de pushdiensten
        defer(fn () => $this->notifyHousehold($user, $household, $progress));

        return $progress;
    }

    public function notifyHousehold(User $user, Household $household, ClassProgress $progress): int
    {
        $sent = 0;

        $household->users()->with('notificationSettings')->get()
            ->each(function (User $member) use ($user, $progress, &$sent) {
                if ($member->id === $user->id
                    || $member->notificationSettings?->enabled === false
                    || $member->pushSubscriptions()->doesntExist()) {
                    return;
                }

                $member->notify(new HeroPrestiged($user->name, "{$progress->rank()['emoji']} {$progress->rank()['label']} {$progress->class->label()}"));
                $sent++;
            });

        return $sent;
    }
}

<?php

namespace App\Actions;

use App\Enums\HeroClass;
use App\Exceptions\HeroException;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Models\XpEvent;
use App\Support\Hero\XpGain;
use App\Support\Today;
use Illuminate\Support\Facades\DB;

/**
 * "Ik heb gekookt": Chef-XP, één keer per dag per persoon.
 * 10 XP; staat het recept vandaag op het weekmenu 15; voor het eerst dit recept gekookt 20.
 */
class CookMeal
{
    public const BASE_XP = 10;

    public const WEEKMENU_XP = 15;

    public const NEW_RECIPE_XP = 20;

    public function handle(User $user, int $householdId, ?Recipe $recipe = null): XpGain
    {
        if ($recipe && $recipe->household_id !== $householdId) {
            throw new HeroException('Dit recept hoort niet bij dit huishouden');
        }

        return DB::transaction(function () use ($user, $householdId, $recipe) {
            // lid-rij op slot: zo kan dubbel tikken niet twee keer XP geven
            $member = DB::table('household_user')
                ->where('household_id', $householdId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                throw new HeroException('Je hoort niet bij dit huishouden');
            }

            if ($this->cookedToday($user, $householdId)) {
                throw new HeroException('Je hebt vandaag al gekookt. Morgen weer!');
            }

            $xp = self::BASE_XP;

            if ($recipe) {
                $onMenu = MealPlanEntry::withoutGlobalScopes()
                    ->where('household_id', $householdId)
                    ->whereDate('date', Today::iso())
                    ->where('recipe_id', $recipe->id)
                    ->exists();

                $cookedBefore = XpEvent::withoutGlobalScopes()
                    ->where('household_id', $householdId)
                    ->where('user_id', $user->id)
                    ->where('source', XpEvent::SOURCE_COOKED)
                    ->where('recipe_id', $recipe->id)
                    ->exists();

                $xp = match (true) {
                    ! $cookedBefore => self::NEW_RECIPE_XP,
                    $onMenu => self::WEEKMENU_XP,
                    default => self::BASE_XP,
                };
            }

            return app(AwardXp::class)->handle($user, $householdId, HeroClass::Chef, $xp, XpEvent::SOURCE_COOKED, [
                'recipe_id' => $recipe?->id,
                'label' => $recipe ? mb_substr($recipe->title, 0, 255) : 'Gekookt',
            ]);
        });
    }

    public function cookedToday(User $user, int $householdId): bool
    {
        return XpEvent::withoutGlobalScopes()
            ->where('household_id', $householdId)
            ->where('user_id', $user->id)
            ->where('source', XpEvent::SOURCE_COOKED)
            ->whereDate('date', Today::iso())
            ->exists();
    }
}

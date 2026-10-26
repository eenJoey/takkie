<?php

namespace App\Actions;

use App\Models\Recipe;
use App\Notifications\RecipeUpdated;

/**
 * Na een inhoudswijziging van een openbaar recept: pushmelding naar wie er een kopie van heeft opgeslagen.
 * Niet naar de bewerker zelf, niet als die persoon geen lid meer is, en niet als meldingen uit staan (profiel).
 */
class NotifyRecipeCopies
{
    public function handle(Recipe $source, ?int $editorId = null): int
    {
        if (! $source->isPublic()) {
            return 0;
        }

        $sent = 0;

        Recipe::withoutGlobalScopes()
            ->where('source_recipe_id', $source->id)
            ->whereNotNull('created_by')
            ->with('creator.notificationSettings')
            ->get()
            ->each(function (Recipe $copy) use ($source, $editorId, &$sent) {
                $user = $copy->creator;

                if (! $user
                    || $user->id === $editorId
                    || $user->notificationSettings?->enabled === false
                    || ! $user->isMemberOf($copy->household_id)
                    || $user->pushSubscriptions()->doesntExist()) {
                    return;
                }

                $user->notify(new RecipeUpdated($source->title, route('recipes.show', $copy->slug, false), $copy->id));
                $sent++;
            });

        return $sent;
    }
}

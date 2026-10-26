<?php

namespace App\Actions;

use App\Enums\RecipeVisibility;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\Scopes\HouseholdScope;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Openbaar recept van een ander huishouden toevoegen aan de eigen recepten.
 * De kopie bevat alles (titel, instructies, ingrediënten, tags), zodat hij blijft bestaan als het origineel verdwijnt.
 * Kopieën zijn privé en alleen-lezen (RecipePolicy). Nog een keer toevoegen geeft de bestaande kopie terug.
 */
class CopyPublicRecipe
{
    public function handle(User $user, Recipe $source, Household $household): Recipe
    {
        if (! $source->isPublic() || $source->household_id === $household->id || ! $user->isMemberOf($household)) {
            throw new AuthorizationException;
        }

        return app(CurrentHousehold::class)->runAs($household, fn () => DB::transaction(function () use ($user, $source) {
            $existing = Recipe::where('source_recipe_id', $source->id)->first();

            if ($existing) {
                return $existing;
            }

            $copy = Recipe::create([
                'created_by' => $user->id,
                'source_recipe_id' => $source->id,
                'title' => $source->title,
                'instructions' => $source->instructions,
                'visibility' => RecipeVisibility::Private,
                'copied_at' => now(),
                'source_synced_at' => $source->content_changed_at,
            ]);

            $source->ingredients()->withoutGlobalScope(HouseholdScope::class)->get()
                ->each(fn ($ingredient) => $copy->ingredients()->create(['text' => $ingredient->text]));

            $source->tags()->withoutGlobalScope(HouseholdScope::class)->get()
                ->each(fn ($tag) => $copy->tags()->create(['name' => $tag->name, 'slug' => $tag->slug]));

            return $copy;
        }));
    }
}

<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Bekijken: openbaar, of lid van het huishouden (ook voor gasten, vandaar ?User).
 * Bewerken, zichtbaarheid en afvinken: alleen leden (Q21: voorlopig elk lid).
 */
class RecipePolicy
{
    public function view(?User $user, Recipe $recipe): bool
    {
        return $recipe->isPublic() || ($user?->isMemberOf($recipe->household_id) ?? false);
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $user->isMemberOf($recipe->household_id);
    }
}

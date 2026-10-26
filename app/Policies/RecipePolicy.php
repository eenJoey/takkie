<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Bekijken: openbaar, of lid van het huishouden (ook voor gasten, vandaar ?User).
 * Bewerken en zichtbaarheid: alleen leden (Q21: voorlopig elk lid), en niet bij een kopie van een openbaar recept.
 * Afvinken: alle leden, ook bij een kopie. Verwijderen: alleen kopieën (eigen recepten hebben nog geen verwijderknop).
 */
class RecipePolicy
{
    public function view(?User $user, Recipe $recipe): bool
    {
        return $recipe->isPublic() || ($user?->isMemberOf($recipe->household_id) ?? false);
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return ! $recipe->isCopy() && $user->isMemberOf($recipe->household_id);
    }

    public function check(User $user, Recipe $recipe): bool
    {
        return $user->isMemberOf($recipe->household_id);
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $recipe->isCopy() && $user->isMemberOf($recipe->household_id);
    }
}

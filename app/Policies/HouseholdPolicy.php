<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

/** RLS van vinkie: leden zien het huishouden, alleen de eigenaar beheert uitnodigingen. */
class HouseholdPolicy
{
    public function view(User $user, Household $household): bool
    {
        return $user->isMemberOf($household);
    }

    public function update(User $user, Household $household): bool
    {
        return $user->isOwnerOf($household);
    }

    public function delete(User $user, Household $household): bool
    {
        return $user->isOwnerOf($household);
    }

    public function manageInvites(User $user, Household $household): bool
    {
        return $user->isOwnerOf($household);
    }

    /** Verlaten kan alleen als lid; de eigenaar kan niet weg (vinkie kent geen overdracht). */
    public function leave(User $user, Household $household): bool
    {
        return $user->isMemberOf($household) && ! $user->isOwnerOf($household);
    }
}

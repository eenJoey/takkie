<?php

namespace App\Policies;

use App\Models\Reward;
use App\Models\User;

/** vinkie-RLS: elk lid mag beloningen aanmaken, verwijderen en inwisselen (geen ouder/admin-rol). */
class RewardPolicy
{
    public function redeem(User $user, Reward $reward): bool
    {
        return $user->isMemberOf($reward->household_id);
    }

    public function delete(User $user, Reward $reward): bool
    {
        return $user->isMemberOf($reward->household_id);
    }
}

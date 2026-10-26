<?php

namespace App\Actions;

use App\Exceptions\RewardException;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Beloning inwisselen (← vinkie-trigger handle_reward_redemption):
 * kosten komen altijd van de beloning, saldo wordt gecontroleerd en afgeschreven. Direct, zonder goedkeuring.
 */
class RedeemReward
{
    public function handle(User $user, Reward $reward): RewardRedemption
    {
        return DB::transaction(function () use ($user, $reward) {
            $member = DB::table('household_user')
                ->where('household_id', $reward->household_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $member || $member->points_balance < $reward->cost_points) {
                throw new RewardException('Onvoldoende punten om deze beloning in te wisselen');
            }

            DB::table('household_user')
                ->where('id', $member->id)
                ->decrement('points_balance', $reward->cost_points);

            return RewardRedemption::create([
                'household_id' => $reward->household_id,
                'reward_id' => $reward->id,
                'redeemed_by' => $user->id,
                'points_spent' => $reward->cost_points,
                'redeemed_at' => now(),
            ]);
        });
    }
}

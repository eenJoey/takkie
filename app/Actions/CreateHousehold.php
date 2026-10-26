<?php

namespace App\Actions;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Support\Facades\DB;

/**
 * Nieuw huishouden: de maker wordt eigenaar (← vinkie-trigger add_owner_as_member),
 * krijgt de vaste lijst "Huishoudtaken" (Q34) en het nieuwe huishouden wordt het huidige.
 */
class CreateHousehold
{
    public function __construct(private CurrentHousehold $current) {}

    public function handle(User $user, string $name): Household
    {
        return DB::transaction(function () use ($user, $name) {
            $household = Household::create(['name' => trim($name)]);
            $household->addMember($user, HouseholdRole::Owner);

            $household->tasksList()->forceFill(['created_by' => $user->id])->save();

            $user->forceFill(['current_household_id' => $household->id])->save();
            $this->current->forget();

            return $household;
        });
    }
}

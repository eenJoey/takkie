<?php

namespace App\Models\Scopes;

use App\Support\CurrentHousehold;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Beperkt elke query tot het huidige huishouden.
 * Fail-closed: zonder huidig huishouden levert de query niets op.
 * Uitzonderingen (console, publieke recepten) gebruiken expliciet withoutGlobalScope(HouseholdScope::class).
 */
class HouseholdScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $householdId = app(CurrentHousehold::class)->id();

        if ($householdId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('household_id'), $householdId);
    }
}

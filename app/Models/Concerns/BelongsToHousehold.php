<?php

namespace App\Models\Concerns;

use App\Models\Household;
use App\Models\Scopes\HouseholdScope;
use App\Support\CurrentHousehold;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Voor alle huishouddata: global scope op het huidige huishouden en household_id automatisch invullen.
 */
trait BelongsToHousehold
{
    public static function bootBelongsToHousehold(): void
    {
        static::addGlobalScope(new HouseholdScope);

        static::creating(function (self $model): void {
            $currentId = app(CurrentHousehold::class)->id();

            if ($model->household_id === null) {
                $model->household_id = $currentId;
            }

            if ($model->household_id === null) {
                throw new LogicException(static::class.' heeft een household_id nodig.');
            }

            if ($currentId !== null && (int) $model->household_id !== $currentId) {
                throw new LogicException(static::class.' mag niet in een ander huishouden worden aangemaakt.');
            }
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}

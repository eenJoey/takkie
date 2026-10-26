<?php

namespace App\Models;

use App\Enums\HouseholdRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class HouseholdMember extends Pivot
{
    protected $table = 'household_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'role' => HouseholdRole::class,
            'points_balance' => 'integer',
            'joined_at' => 'datetime',
        ];
    }
}

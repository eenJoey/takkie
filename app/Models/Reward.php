<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['household_id', 'title', 'description', 'cost_points', 'icon', 'created_by', 'archived'])]
class Reward extends Model
{
    use BelongsToHousehold;

    /** De twaalf emoji's uit vinkie's beloningsformulier */
    public const ICONS = ['🎁', '🎮', '🍿', '🍕', '🍦', '🛍️', '🎬', '💤', '📱', '🚲', '💰', '⚽'];

    protected function casts(): array
    {
        return [
            'cost_points' => 'integer',
            'archived' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}

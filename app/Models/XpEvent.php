<?php

namespace App\Models;

use App\Enums\HeroClass;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén keer XP verdiend in een klasse: door een taak af te vinken of door te koken. */
#[Fillable(['household_id', 'user_id', 'hero_class', 'amount', 'source', 'task_completion_id', 'recipe_id', 'label', 'date', 'created_at'])]
class XpEvent extends Model
{
    use BelongsToHousehold;

    public const SOURCE_TASK = 'task';

    public const SOURCE_COOKED = 'cooked';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'hero_class' => HeroClass::class,
            'amount' => 'integer',
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

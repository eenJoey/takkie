<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Regel van het boodschappenlijstje van een recept (← takkie recipe_items). */
#[Fillable(['household_id', 'recipe_id', 'text', 'is_checked'])]
class RecipeIngredient extends Model
{
    use BelongsToHousehold;

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

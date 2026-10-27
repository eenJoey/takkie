<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén dag in het weekmenu: een eigen recept of een vrij ingevuld gerecht. */
#[Fillable(['household_id', 'date', 'recipe_id', 'title', 'created_by'])]
class MealPlanEntry extends Model
{
    use BelongsToHousehold;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

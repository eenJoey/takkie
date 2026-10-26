<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tag op een recept; hangt aan het huishouden (geen gedeelde woordenlijst). Zoeken gaat via de slug. */
#[Fillable(['household_id', 'recipe_id', 'name', 'slug'])]
class RecipeTag extends Model
{
    use BelongsToHousehold;

    public $timestamps = false;

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

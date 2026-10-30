<?php

namespace App\Models;

use App\Enums\HeroClass;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Prestige per klasse: hoe vaak iemand terug is gegaan naar level 1, en hoeveel XP dat kostte. */
#[Fillable(['household_id', 'user_id', 'hero_class', 'prestige', 'xp_spent'])]
class HeroPrestige extends Model
{
    use BelongsToHousehold;

    protected function casts(): array
    {
        return [
            'hero_class' => HeroClass::class,
            'prestige' => 'integer',
            'xp_spent' => 'integer',
        ];
    }
}

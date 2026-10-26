<?php

namespace App\Models;

use App\Enums\CategoryColor;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['household_id', 'list_id', 'name', 'color'])]
class ListCategory extends Model
{
    use BelongsToHousehold, HasFactory;

    protected function casts(): array
    {
        return [
            'color' => CategoryColor::class,
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

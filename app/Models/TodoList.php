<?php

namespace App\Models;

use App\Enums\ListType;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** "List" is een gereserveerd woord in PHP, vandaar TodoList (tabel: lists). */
#[Fillable(['household_id', 'name', 'type', 'created_by'])]
class TodoList extends Model
{
    use BelongsToHousehold, HasFactory;

    protected $table = 'lists';

    protected $attributes = [
        'type' => 'checklist',
    ];

    protected function casts(): array
    {
        return [
            'type' => ListType::class,
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ListCategory::class, 'list_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'list_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isChecklist(): bool
    {
        return $this->type === ListType::Checklist;
    }
}

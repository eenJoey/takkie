<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'household_id', 'list_id', 'list_category_id', 'title', 'description', 'points',
    'recurrence_type', 'recurrence_interval', 'due_date', 'assigned_to', 'created_by', 'completed_at',
])]
class Task extends Model
{
    use BelongsToHousehold, HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'recurrence_type' => RecurrenceType::class,
            'recurrence_interval' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ListCategory::class, 'list_category_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

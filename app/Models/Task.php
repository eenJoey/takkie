<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(Carbon $today): bool
    {
        return $this->due_date !== null && $this->due_date->lt($today);
    }

    /** Korte datum zoals vinkie (nl-NL, "26 okt"), zonder de punt die Carbon achter de maand zet. */
    public function dueLabel(): string
    {
        return $this->due_date ? rtrim($this->due_date->locale('nl')->translatedFormat('j M'), '.') : '';
    }

    /** vinkie: "Eenmalig" of "Elke 2d" / "Elke 1w" / "Elke 3m". */
    public function recurrenceLabel(): string
    {
        return match ($this->recurrence_type) {
            RecurrenceType::Once => 'Eenmalig',
            RecurrenceType::Daily => "Elke {$this->recurrence_interval}d",
            RecurrenceType::Weekly => "Elke {$this->recurrence_interval}w",
            RecurrenceType::Monthly => "Elke {$this->recurrence_interval}m",
        };
    }

    /** Valt deze (herhalende) taak op die datum? Port van vinkie's occursOn() voor de dagnavigatie. */
    public function occursOn(Carbon $date): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        $base = $this->due_date->copy()->startOfDay();
        $date = $date->copy()->startOfDay();

        if ($base->equalTo($date)) {
            return true;
        }

        $interval = max(1, $this->recurrence_interval);
        $diff = (int) $base->diffInDays($date, false);

        if ($this->recurrence_type === RecurrenceType::Once || $diff <= 0) {
            return false;
        }

        return match ($this->recurrence_type) {
            RecurrenceType::Daily => $diff % $interval === 0,
            RecurrenceType::Weekly => $diff % ($interval * 7) === 0,
            RecurrenceType::Monthly => (function () use ($base, $date, $interval) {
                $months = ($date->year - $base->year) * 12 + ($date->month - $base->month);

                return $months > 0 && $months % $interval === 0 && $date->day === $base->day;
            })(),
        };
    }
}

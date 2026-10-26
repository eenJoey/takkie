<?php

namespace App\Models;

use App\Enums\HouseholdRole;
use App\Enums\ListType;
use App\Models\Scopes\HouseholdScope;
use App\Support\CurrentHousehold;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Household extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(HouseholdMember::class)
            ->withPivot(['id', 'role', 'points_balance', 'joined_at'])
            ->withTimestamps();
    }

    public function lists(): HasMany
    {
        return $this->hasMany(TodoList::class);
    }

    /**
     * De vaste lijst "Huishoudtaken" van dit huishouden (Q34). Ontbreekt hij (bijv. na handmatig wijzigen
     * in de database), dan wordt hij leeg opnieuw aangemaakt, zodat Taken en Vandaag altijd werken.
     */
    public function tasksList(): TodoList
    {
        $list = TodoList::withoutGlobalScope(HouseholdScope::class)
            ->where('household_id', $this->id)
            ->where('type', ListType::Tasks)
            ->oldest('id')
            ->first();

        return $list ?? app(CurrentHousehold::class)->runAs($this, fn () => TodoList::create([
            'name' => 'Huishoudtaken',
            'type' => ListType::Tasks,
        ]));
    }

    public function invites(): HasMany
    {
        return $this->hasMany(HouseholdInvite::class);
    }

    public function addMember(User $user, HouseholdRole $role = HouseholdRole::Member): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['role' => $role->value, 'joined_at' => now()],
        ]);
    }
}

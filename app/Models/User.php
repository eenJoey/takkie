<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\HouseholdRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'current_household_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class)
            ->using(HouseholdMember::class)
            ->withPivot(['id', 'role', 'points_balance', 'joined_at'])
            ->withTimestamps();
    }

    public function currentHousehold(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'current_household_id');
    }

    public function isMemberOf(Household|int|null $household): bool
    {
        $id = $household instanceof Household ? $household->id : $household;

        return $id !== null && $this->households()->whereKey($id)->exists();
    }

    public function isOwnerOf(Household|int|null $household): bool
    {
        $id = $household instanceof Household ? $household->id : $household;

        return $id !== null && $this->households()
            ->whereKey($id)
            ->wherePivot('role', HouseholdRole::Owner->value)
            ->exists();
    }

    /** Eerste letter van de naam, voor het avatar-rondje (vinkie UserAvatar zonder foto). */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: $this->email, 0, 1));
    }
}

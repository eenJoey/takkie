<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\HouseholdRole;
use App\Enums\StartPage;
use App\Enums\Theme;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'current_household_id', 'start_page', 'theme', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

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
            'start_page' => StartPage::class,
            'theme' => Theme::class,
        ];
    }

    public function households(): BelongsToMany
    {
        return $this->belongsToMany(Household::class)
            ->using(HouseholdMember::class)
            ->withPivot(['id', 'role', 'points_balance', 'joined_at'])
            ->withTimestamps();
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function currentHousehold(): BelongsTo
    {
        return $this->belongsTo(Household::class, 'current_household_id');
    }

    /** Het tabblad dat Takkie opent bij het starten (/). */
    public function startPage(): StartPage
    {
        return $this->start_page ?? StartPage::Lists;
    }

    /** De gekozen kleurstelling (profiel). */
    public function theme(): Theme
    {
        return $this->theme ?? Theme::TakkieLight;
    }

    /**
     * Google-accounts hebben geen wachtwoord (null). Laravel accepteert het onthoud-cookie alleen als dit een string is
     * (SessionGuard::userFromRecaller), dus zonder deze '' werd je na het verlopen van de sessie toch uitgelogd.
     * Inloggen met een wachtwoord blijft onmogelijk: geen enkel wachtwoord klopt met ''.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password;
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

    /** URL voor de profielfoto: eigen upload via de beveiligde route, of de externe Google-foto. */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return route('avatars.show', ['user' => $this->id, 'v' => md5($this->avatar)]);
    }

    /** Deelt deze gebruiker een huishouden met $other? (← vinkie shares_household) */
    public function sharesHouseholdWith(User $other): bool
    {
        return $this->id === $other->id
            || $this->households()->whereIn('households.id', $other->households()->select('households.id'))->exists();
    }

    /** Eerste letter van de naam, voor het avatar-rondje (vinkie UserAvatar zonder foto). */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: $this->email, 0, 1));
    }
}

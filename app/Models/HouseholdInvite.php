<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHousehold;
use App\Models\Scopes\HouseholdScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Uitnodiging voor een huishouden: eenmalig te gebruiken, 14 dagen geldig (zoals vinkie). */
#[Fillable(['household_id', 'code', 'email', 'created_by', 'expires_at', 'accepted'])]
class HouseholdInvite extends Model
{
    use BelongsToHousehold, HasFactory;

    public const MIN_CODE_LENGTH = 12;

    protected static function booted(): void
    {
        static::creating(function (self $invite): void {
            $invite->code ??= Str::random(32);
            $invite->expires_at ??= now()->addDays(14);
        });
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function url(): string
    {
        return route('invites.show', $this->code);
    }

    /**
     * Opzoeken op code buiten de huishoud-scope (equivalent van vinkie's get_invite_info):
     * alleen geldige, open uitnodigingen.
     */
    public static function findOpenByCode(string $code): ?self
    {
        if (mb_strlen($code) < self::MIN_CODE_LENGTH) {
            return null;
        }

        return static::withoutGlobalScope(HouseholdScope::class)
            ->where('code', $code)
            ->where('accepted', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('accepted', false);
    }
}

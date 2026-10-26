<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Meldingsinstellingen per gebruiker (vinkie lovable notification_settings). */
#[Fillable(['user_id', 'enabled', 'times', 'timezone', 'only_assigned'])]
class NotificationSetting extends Model
{
    public const MAX_TIMES = 5;

    protected $attributes = [
        'enabled' => true,
        'times' => '["08:00"]',
        'timezone' => 'Europe/Amsterdam',
        'only_assigned' => false,
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'times' => 'array',
            'only_assigned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Valt "nu" 0–15 minuten na een van de meldingsmomenten, in de tijdzone van de gebruiker? (vinkie matchesSlot) */
    public function matchesSlot(Carbon $now): bool
    {
        $local = $now->copy()->setTimezone($this->safeTimezone());
        $minutesNow = $local->hour * 60 + $local->minute;

        foreach ($this->times ?? [] as $time) {
            if (! preg_match('/^(\d{1,2}):(\d{2})$/', (string) $time, $m)) {
                continue;
            }

            $diff = $minutesNow - ((int) $m[1] * 60 + (int) $m[2]);

            if ($diff >= 0 && $diff < 15) {
                return true;
            }
        }

        return false;
    }

    public function localDate(Carbon $now): string
    {
        return $now->copy()->setTimezone($this->safeTimezone())->toDateString();
    }

    private function safeTimezone(): string
    {
        return in_array($this->timezone, timezone_identifiers_list(), true) ? $this->timezone : 'Europe/Amsterdam';
    }
}

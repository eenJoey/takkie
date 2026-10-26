<?php

namespace App\Actions;

use App\Enums\HouseholdRole;
use App\Exceptions\InviteException;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\Scopes\HouseholdScope;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Uitnodiging accepteren (← vinkie-RPC accept_invite): eenmalig, 14 dagen geldig, maakt het huishouden actief. */
class AcceptInvite
{
    public function __construct(private CurrentHousehold $current) {}

    public function handle(User $user, string $code): Household
    {
        $code = self::extractCode($code);

        if (mb_strlen($code) < HouseholdInvite::MIN_CODE_LENGTH) {
            throw new InviteException('Ongeldige uitnodiging');
        }

        return DB::transaction(function () use ($user, $code) {
            $invite = HouseholdInvite::withoutGlobalScope(HouseholdScope::class)
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $invite) {
                throw new InviteException('Ongeldige uitnodiging');
            }

            if ($invite->accepted) {
                throw new InviteException('Uitnodiging al gebruikt');
            }

            if ($invite->expires_at->isPast()) {
                throw new InviteException('Uitnodiging is verlopen');
            }

            $household = $invite->household;

            if (! $user->isMemberOf($household)) {
                $household->addMember($user, HouseholdRole::Member);
            }

            $invite->forceFill(['accepted' => true])->save();
            $user->forceFill(['current_household_id' => $household->id])->save();
            $this->current->forget();

            return $household;
        });
    }

    /** Accepteert zowel de kale code als de volledige uitnodigingslink. */
    public static function extractCode(string $input): string
    {
        $input = trim($input);

        return Str::contains($input, '/invite/') ? Str::afterLast(rtrim($input, '/'), '/invite/') : $input;
    }
}

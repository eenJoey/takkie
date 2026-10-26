<?php

namespace App\Support;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Het huishouden waarin de ingelogde gebruiker nu werkt.
 *
 * Bron: users.current_household_id, maar alleen als de gebruiker daar (nog) lid van is;
 * anders het oudste lidmaatschap. Zonder gebruiker of lidmaatschap: null (de scope is dan fail-closed).
 */
class CurrentHousehold
{
    private ?Household $household = null;

    private bool $resolved = false;

    private bool $manual = false;

    private ?int $resolvedForUserId = null;

    public function get(): ?Household
    {
        if ($this->manual) {
            return $this->household;
        }

        $userId = Auth::id();

        // opnieuw bepalen als er sindsdien (anders) is ingelogd
        if (! $this->resolved || $this->resolvedForUserId !== $userId) {
            $this->household = $this->resolve(Auth::user());
            $this->resolvedForUserId = $userId;
            $this->resolved = true;
        }

        return $this->household;
    }

    public function id(): ?int
    {
        return $this->get()?->id;
    }

    /** Expliciet vastzetten (seeders, console, wisselen van huishouden). */
    public function set(?Household $household): void
    {
        $this->household = $household;
        $this->manual = true;
    }

    /**
     * Voer iets uit binnen een ander huishouden (bijv. bij het aanmaken van een nieuw huishouden),
     * en zet daarna de vorige toestand terug.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runAs(Household $household, callable $callback): mixed
    {
        $previous = [$this->household, $this->manual, $this->resolved, $this->resolvedForUserId];
        $this->set($household);

        try {
            return $callback();
        } finally {
            [$this->household, $this->manual, $this->resolved, $this->resolvedForUserId] = $previous;
        }
    }

    public function forget(): void
    {
        $this->household = null;
        $this->manual = false;
        $this->resolved = false;
        $this->resolvedForUserId = null;
    }

    private function resolve(?User $user): ?Household
    {
        if (! $user) {
            return null;
        }

        $households = $user->households()->orderBy('household_user.joined_at')->get();

        return $households->firstWhere('id', $user->current_household_id) ?? $households->first();
    }
}

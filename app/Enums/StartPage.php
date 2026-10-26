<?php

namespace App\Enums;

/** De tabbladen uit het menu. Eén ervan kiest de gebruiker als starttabblad (profiel); null = Lijstjes. */
enum StartPage: string
{
    case Lists = 'lists';
    case Dashboard = 'dashboard';
    case Tasks = 'tasks';
    case Rewards = 'rewards';
    case Recipes = 'recipes';
    case Household = 'household';

    public function label(): string
    {
        return match ($this) {
            self::Lists => 'Lijstjes',
            self::Dashboard => 'Vandaag',
            self::Tasks => 'Taken',
            self::Rewards => 'Beloningen',
            self::Recipes => 'Recepten',
            self::Household => 'Huishouden',
        };
    }

    public function route(): string
    {
        return match ($this) {
            self::Lists => 'lists.index',
            self::Dashboard => 'dashboard',
            self::Tasks => 'tasks',
            self::Rewards => 'rewards',
            self::Recipes => 'recipes.index',
            self::Household => 'household',
        };
    }

    /** Routepatroon waarop het menu-item actief is. */
    public function activePattern(): string
    {
        return match ($this) {
            self::Lists => 'lists.*',
            self::Recipes => 'recipes.*',
            default => $this->route(),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Lists => 'list-checks',
            self::Dashboard => 'home',
            self::Tasks => 'clipboard-check',
            self::Rewards => 'gift',
            self::Recipes => 'book-open',
            self::Household => 'users',
        };
    }

    /** Taken (via Vandaag) en Huishouden (via profiel) staan op mobiel niet in de balk onderin. */
    public function inBottomNav(): bool
    {
        return ! in_array($this, [self::Tasks, self::Household], true);
    }
}

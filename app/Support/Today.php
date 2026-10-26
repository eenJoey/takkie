<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * "Vandaag" in de tijdzone van de gebruikers (config app.local_timezone, standaard Europe/Amsterdam).
 * Opslag blijft UTC; vervaldatums (DATE) en "vandaag" rekenen we lokaal, zodat het rond middernacht klopt.
 */
class Today
{
    public static function now(): Carbon
    {
        return now(config('app.local_timezone'));
    }

    /**
     * De lokale kalenderdatum als middernacht in de app-tijdzone (UTC), net als de 'date'-cast van due_date.
     * Zo zijn vergelijkingen met vervaldatums puur op datum.
     */
    public static function date(): Carbon
    {
        return Carbon::parse(self::iso());
    }

    public static function iso(): string
    {
        return self::now()->toDateString();
    }
}

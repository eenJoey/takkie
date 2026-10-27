<?php

namespace App\Support;

/** Snel toevoegen: "melk, brood, kaas" (komma, puntkomma of nieuwe regel) wordt drie losse items. */
class QuickAdd
{
    /** @return list<string> */
    public static function split(string $input, int $max = 50): array
    {
        return collect(preg_split('/[,;\r\n]+/u', $input))
            ->map(fn (string $item) => mb_substr(trim($item), 0, 255))
            ->filter(fn (string $item) => $item !== '')
            ->take($max)
            ->values()
            ->all();
    }
}

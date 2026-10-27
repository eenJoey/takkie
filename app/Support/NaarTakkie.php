<?php

namespace App\Support;

/**
 * "Naar Takkie": het script dat in de browser van de gebruiker het recept uit de geopende pagina leest
 * (JSON-LD of Next.js-data) en Takkie opent met het recept in het URL-fragment (#import=…).
 * Zo werkt het ook bij sites die ophalen door de server blokkeren, zoals AH: de pagina is gewoon in jouw browser geladen.
 */
class NaarTakkie
{
    /** Voor een bladwijzer (computer, Android): opent Takkie in hetzelfde tabblad. */
    public static function bookmarklet(): string
    {
        return 'javascript:'.rawurlencode(self::script('location.href=u;'));
    }

    /** Voor de iPhone-Opdracht ("Voer JavaScript uit op webpagina"): geeft de link terug aan de Opdracht. */
    public static function shortcutScript(): string
    {
        return self::script('completion(u);');
    }

    private static function script(string $done): string
    {
        $source = trim(file_get_contents(resource_path('naar-takkie/script.js')));

        return strtr($source, [
            '__TAKKIE__' => json_encode(route('recipes.create'), JSON_UNESCAPED_SLASHES),
            '__DONE__' => $done,
        ]);
    }
}

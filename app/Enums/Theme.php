<?php

namespace App\Enums;

/**
 * Kleurstelling per gebruiker (profiel). De kleuren zelf staan in resources/css/app.css onder [data-theme='…'].
 * Licht en donker zijn losse kleurstellingen; de gebruiker ziet geen namen, alleen een kleurbalkje.
 */
enum Theme: string
{
    case TakkieLight = 'takkie-light';
    case TakkieDark = 'takkie-dark';
    case BabyBlueLight = 'babyblue-light';
    case BabyBlueDark = 'babyblue-dark';
    case OceanLight = 'ocean-light';
    case OceanDark = 'ocean-dark';
    case RainforestLight = 'rainforest-light';
    case RainforestDark = 'rainforest-dark';
    case MonokaiLight = 'monokai-light';
    case MonokaiDark = 'monokai-dark';

    /** Kleur voor <meta name="theme-color"> (adresbalk/statusbalk), gelijk aan de achtergrond. */
    public function metaColor(): string
    {
        return match ($this) {
            self::TakkieLight => '#faf6f3',
            self::TakkieDark => '#221c24',
            self::BabyBlueLight => '#f4f8fc',
            self::BabyBlueDark => '#17202b',
            self::OceanLight => '#f2f8fa',
            self::OceanDark => '#0b1e2d',
            self::RainforestLight => '#f4f7f0',
            self::RainforestDark => '#12201a',
            self::MonokaiLight => '#faf4f2',
            self::MonokaiDark => '#272822',
        };
    }
}

<?php

namespace App\Enums;

/** De zes takkie-categoriekleuren (tokens --color-cat-* in resources/css/app.css). */
enum CategoryColor: string
{
    case Sky = 'sky';
    case Mint = 'mint';
    case Lavender = 'lavender';
    case Peach = 'peach';
    case Sun = 'sun';
    case Pink = 'pink';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Sky => 'bg-cat-sky',
            self::Mint => 'bg-cat-mint',
            self::Lavender => 'bg-cat-lavender',
            self::Peach => 'bg-cat-peach',
            self::Sun => 'bg-cat-sun',
            self::Pink => 'bg-cat-pink',
        };
    }
}

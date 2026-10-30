<?php

namespace App\Enums;

/** Vaste klassen voor "Mijn held": een taak levert XP op in de klasse die erbij hoort. */
enum HeroClass: string
{
    case Chef = 'chef';
    case Cleaner = 'cleaner';
    case Laundry = 'laundry';
    case Gardener = 'gardener';
    case Handyman = 'handyman';
    case Shopper = 'shopper';

    public function label(): string
    {
        return match ($this) {
            self::Chef => 'Chef',
            self::Cleaner => 'Schoonmaker',
            self::Laundry => 'Washeld',
            self::Gardener => 'Tuinier',
            self::Handyman => 'Klusser',
            self::Shopper => 'Boodschapper',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Chef => '🍳',
            self::Cleaner => '🧹',
            self::Laundry => '🧺',
            self::Gardener => '🌿',
            self::Handyman => '🔧',
            self::Shopper => '🛒',
        };
    }

    /** Elke 5 levels een nieuwe titel: index 0 = level 1–4, 9 = level 45 en hoger. */
    public function titles(): array
    {
        return match ($this) {
            self::Chef => ['Afwasser', 'Keukenhulp', 'Groentesnijder', 'Hulpkok', 'Kok', 'Sauzenmaker', 'Souschef', 'Chef', 'Meesterchef', 'Sterrenchef'],
            self::Cleaner => ['Stoffer', 'Veger', 'Poetser', 'Dweilridder', 'Glansmaker', 'Vlekkenjager', 'Stofbestrijder', 'Schoonmeester', 'Glansmeester', 'Smetteloze'],
            self::Laundry => ['Sokkenzoeker', 'Wasknijper', 'Sorteerder', 'Trommeldraaier', 'Vouwer', 'Strijkbout', 'Vlekkenkenner', 'Vouwkunstenaar', 'Wasmeester', 'Washeld'],
            self::Gardener => ['Zaadje', 'Kiemplant', 'Wieder', 'Gieter', 'Snoeier', 'Groene vinger', 'Kweker', 'Hovenier', 'Tuinmeester', 'Druïde'],
            self::Handyman => ['Prutser', 'Schroevendraaier', 'Plakker', 'Boorder', 'Klusjesman', 'Handige Harry', 'Vakman', 'Bouwer', 'Meesterbouwer', 'Architect'],
            self::Shopper => ['Mandjesdrager', 'Lijstjeslezer', 'Koopjesjager', 'Voorraadhouder', 'Karrenduwer', 'Schapkenner', 'Bonuskoning', 'Marktmeester', 'Kartkoning', 'Bevoorradingsheld'],
        };
    }

    public function title(int $level): string
    {
        $titles = $this->titles();

        return $titles[min(count($titles) - 1, intdiv(max(1, $level), 5))];
    }

    /** Gok een klasse uit de titel van een taak, als voorstel in het formulier. */
    public static function guess(string $title): ?self
    {
        $title = mb_strtolower($title);

        $words = [
            self::Chef->value => ['kook', 'koken', 'eten', 'afwas', 'vaatwas', 'bak', 'lunch', 'ontbijt', 'diner', 'maaltijd'],
            self::Laundry->value => ['was', 'strijk', 'vouw', 'bed verschon', 'beddengoed', 'handdoek'],
            self::Cleaner->value => ['stofzuig', 'dweil', 'poets', 'schoon', 'stof', 'badkamer', 'wc', 'toilet', 'ramen', 'opruim', 'vuilnis', 'afval', 'container', 'keuken'],
            self::Gardener->value => ['tuin', 'gras', 'maai', 'plant', 'onkruid', 'snoei', 'water geven', 'bloem', 'heg'],
            self::Handyman->value => ['repar', 'klus', 'maken', 'ophang', 'boor', 'lamp', 'schilder', 'monteer', 'fiets'],
            self::Shopper->value => ['boodschap', 'winkel', 'super', 'inkop', 'kopen', 'halen', 'apotheek'],
        ];

        foreach ($words as $class => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($title, $needle)) {
                    return self::from($class);
                }
            }
        }

        return null;
    }
}

<?php

namespace App\Support\Hero;

/**
 * Rekenregels voor levels: van level n naar n+1 kost 10 + 2n XP.
 * Met 1 taak (min. 10 XP) per dag per klasse haal je level 50, en dus prestige, in ~9,5 maand.
 */
final class Levels
{
    public const MAX_LEVEL = 50;

    public const MAX_PRESTIGE = 5;

    public const BONUS_PER_PRESTIGE = 0.1;

    /** Minimale XP per afgevinkte taak, zodat taken met weinig punten je niet afremmen. */
    public const MIN_TASK_XP = 10;

    public const RANKS = [
        1 => ['label' => 'Brons', 'emoji' => '🥉'],
        2 => ['label' => 'Zilver', 'emoji' => '🥈'],
        3 => ['label' => 'Goud', 'emoji' => '🥇'],
        4 => ['label' => 'Diamant', 'emoji' => '💎'],
        5 => ['label' => 'Legende', 'emoji' => '👑'],
    ];

    /** XP om van $level naar $level + 1 te gaan. */
    public static function cost(int $level): int
    {
        return 10 + 2 * max(1, $level);
    }

    /** Totale XP vanaf level 1 tot het begin van $level. */
    public static function totalFor(int $level): int
    {
        $n = max(1, $level) - 1;

        return 10 * $n + $n * ($n + 1);
    }

    /** XP die een prestige kost: precies level 1 → 50. */
    public static function prestigeCost(): int
    {
        return self::totalFor(self::MAX_LEVEL);
    }

    public static function levelFor(int $xp, ?int $cap = null): int
    {
        // n² + 11n − xp ≤ 0 → n = floor((−11 + √(121 + 4·xp)) / 2), daarna afronding rechtzetten
        $level = 1 + (int) floor((-11 + sqrt(121 + 4 * max(0, $xp))) / 2);

        while (self::totalFor($level + 1) <= $xp) {
            $level++;
        }

        while ($level > 1 && self::totalFor($level) > $xp) {
            $level--;
        }

        return $cap === null ? $level : min($cap, $level);
    }

    public static function multiplier(int $prestige): float
    {
        return 1 + self::BONUS_PER_PRESTIGE * min(self::MAX_PRESTIGE, max(0, $prestige));
    }

    /** @return array{label: string, emoji: string}|null */
    public static function rank(int $prestige): ?array
    {
        return self::RANKS[min(self::MAX_PRESTIGE, $prestige)] ?? null;
    }
}

<?php

namespace App\Support;

/**
 * Porties omrekenen: schaalt de hoeveelheid aan het begin van een ingrediënt ("300 g gehakt", "1/2 ui", "1,5 el", "2-3 tenen", "½ tl").
 * Zonder getal vooraan ("naar smaak zout") blijft de tekst gelijk.
 */
class IngredientScaler
{
    private const FRACTIONS = ['½' => 0.5, '¼' => 0.25, '¾' => 0.75, '⅓' => 1 / 3, '⅔' => 2 / 3, '⅛' => 0.125];

    private const NUMBER = '(?:\d+\s+\d+/\d+|\d+/\d+|\d+[½¼¾⅓⅔⅛]|\d+(?:[.,]\d+)?|[½¼¾⅓⅔⅛])';

    public static function scale(string $text, float $factor): string
    {
        if (abs($factor - 1) < 0.0001) {
            return $text;
        }

        $pattern = '#^(\s*)('.self::NUMBER.')(?:(\s*[-–]\s*)('.self::NUMBER.'))?(?=\s|\p{L}|$)#u';

        return preg_replace_callback($pattern, function (array $m) use ($factor): string {
            $result = $m[1].self::format(self::value($m[2]) * $factor);

            if (! empty($m[4])) {
                $result .= $m[3].self::format(self::value($m[4]) * $factor);
            }

            return $result;
        }, $text, 1);
    }

    private static function value(string $number): float
    {
        $number = trim($number);

        if (isset(self::FRACTIONS[$number])) {
            return self::FRACTIONS[$number];
        }

        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $number, $m)) {
            return (int) $m[1] + ((int) $m[3] ? (int) $m[2] / (int) $m[3] : 0);
        }

        if (preg_match('/^(\d+)\/(\d+)$/', $number, $m)) {
            return (int) $m[2] ? (int) $m[1] / (int) $m[2] : 0;
        }

        if (preg_match('/^(\d+)([½¼¾⅓⅔⅛])$/u', $number, $m)) {
            return (int) $m[1] + self::FRACTIONS[$m[2]];
        }

        return (float) str_replace(',', '.', $number);
    }

    /** 10 of meer: hele getallen; daaronder 1 decimaal; onder 1: 2 decimalen. Nederlandse komma. */
    private static function format(float $value): string
    {
        $decimals = match (true) {
            $value >= 10 => 0,
            $value >= 1 => 1,
            default => 2,
        };

        $formatted = number_format(round($value, $decimals), $decimals, ',', '');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }
}

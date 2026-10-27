<?php

namespace Tests\Unit;

use App\Support\IngredientScaler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IngredientScalerTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'gram' => ['300 g gehakt', 2, '600 g gehakt'],
            'halveren' => ['300 g gehakt', 0.5, '150 g gehakt'],
            'komma' => ['1,5 el olijfolie', 2, '3 el olijfolie'],
            'breuk' => ['1/2 ui', 2, '1 ui'],
            'gemengde breuk' => ['1 1/2 dl melk', 2, '3 dl melk'],
            'unicode breuk' => ['½ tl zout', 0.5, '0,25 tl zout'],
            'getal met unicode breuk' => ['1½ kg aardappels', 2, '3 kg aardappels'],
            'bereik' => ['2-3 tenen knoflook', 2, '4-6 tenen knoflook'],
            'vast aan eenheid' => ['400g tomatenblokjes', 1.5, '600g tomatenblokjes'],
            'afronden' => ['1 ei', 4 / 3, '1,3 ei'],
            'zonder getal' => ['naar smaak crème fraîche', 2, 'naar smaak crème fraîche'],
            'getal later' => ['wraps (8 stuks)', 2, 'wraps (8 stuks)'],
            'factor 1' => ['1/2 ui', 1, '1/2 ui'],
        ];
    }

    #[DataProvider('cases')]
    public function test_scales_the_leading_quantity(string $text, float $factor, string $expected): void
    {
        $this->assertSame($expected, IngredientScaler::scale($text, $factor));
    }
}

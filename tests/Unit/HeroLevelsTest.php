<?php

namespace Tests\Unit;

use App\Enums\HeroClass;
use App\Support\Hero\ClassProgress;
use App\Support\Hero\Levels;
use PHPUnit\Framework\TestCase;

/** Levelcurve: 10 + 2n XP per level, prestige op 50 binnen een jaar bij 1 taak (10 XP) per dag. */
class HeroLevelsTest extends TestCase
{
    public function test_levels_follow_the_curve(): void
    {
        $this->assertSame(1, Levels::levelFor(0));
        $this->assertSame(1, Levels::levelFor(11));
        $this->assertSame(2, Levels::levelFor(12));
        $this->assertSame(10, Levels::levelFor(180));
        $this->assertSame(9, Levels::levelFor(179));
        $this->assertSame(25, Levels::levelFor(840));

        foreach ([1, 2, 7, 33, 50, 120] as $level) {
            $this->assertSame($level, Levels::levelFor(Levels::totalFor($level)));
            $this->assertSame($level, Levels::levelFor(Levels::totalFor($level + 1) - 1));
        }
    }

    public function test_prestige_takes_less_than_a_year_at_one_task_a_day(): void
    {
        $this->assertSame(2940, Levels::prestigeCost());
        $this->assertLessThan(365, Levels::prestigeCost() / Levels::MIN_TASK_XP);
    }

    public function test_level_stops_at_50_until_prestige_and_runs_on_after_legend(): void
    {
        $maxed = new ClassProgress(HeroClass::Chef, 99999, 0, 0);
        $this->assertSame(50, $maxed->level);
        $this->assertTrue($maxed->canPrestige());
        $this->assertSame('Sterrenchef', $maxed->title());

        $legend = new ClassProgress(HeroClass::Chef, 99999, 5, 5 * Levels::prestigeCost());
        $this->assertGreaterThan(50, $legend->level);
        $this->assertFalse($legend->canPrestige());
        $this->assertSame('Legende Sterrenchef', $legend->fullTitle());
        $this->assertSame(50, $legend->bonusPercent());
    }

    public function test_hero_level_counts_a_prestige_as_fifty_levels(): void
    {
        $progress = new ClassProgress(HeroClass::Cleaner, Levels::prestigeCost() + Levels::totalFor(3), 1, Levels::prestigeCost());

        $this->assertSame(3, $progress->level);
        $this->assertSame(53, $progress->heroLevels());
        $this->assertSame('Brons Stoffer', $progress->fullTitle());
    }

    public function test_titles_change_every_five_levels(): void
    {
        $this->assertSame('Afwasser', HeroClass::Chef->title(1));
        $this->assertSame('Afwasser', HeroClass::Chef->title(4));
        $this->assertSame('Keukenhulp', HeroClass::Chef->title(5));
        $this->assertSame('Sterrenchef', HeroClass::Chef->title(45));
    }

    public function test_class_is_guessed_from_the_task_title(): void
    {
        $this->assertSame(HeroClass::Chef, HeroClass::guess('Afwassen'));
        $this->assertSame(HeroClass::Laundry, HeroClass::guess('Was ophangen'));
        $this->assertSame(HeroClass::Cleaner, HeroClass::guess('Badkamer schoonmaken'));
        $this->assertSame(HeroClass::Gardener, HeroClass::guess('Gras maaien'));
        $this->assertSame(HeroClass::Shopper, HeroClass::guess('Boodschappen doen'));
        $this->assertNull(HeroClass::guess('Belastingaangifte'));
    }
}

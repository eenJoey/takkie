<?php

namespace App\Support\Hero;

use App\Enums\HeroClass;

/** Stand van één klasse voor één persoon. */
final class ClassProgress
{
    public readonly int $level;

    /** XP in de huidige ronde (na aftrek van prestiges). */
    public readonly int $xp;

    public function __construct(
        public readonly HeroClass $class,
        public readonly int $earned,
        public readonly int $prestige,
        int $spent,
    ) {
        $this->xp = max(0, $earned - $spent);
        // na Legende eindeloos doorlevelen; daarvoor stopt het op 50 tot je prestige kiest
        $this->level = Levels::levelFor($this->xp, $this->isLegend() ? null : Levels::MAX_LEVEL);
    }

    public function isLegend(): bool
    {
        return $this->prestige >= Levels::MAX_PRESTIGE;
    }

    public function isMaxed(): bool
    {
        return ! $this->isLegend() && $this->level >= Levels::MAX_LEVEL;
    }

    public function canPrestige(): bool
    {
        return $this->isMaxed();
    }

    public function title(): string
    {
        return $this->class->title($this->level);
    }

    /** Bijv. "Goud Sterrenchef" */
    public function fullTitle(): string
    {
        $rank = Levels::rank($this->prestige);

        return trim(($rank['label'] ?? '').' '.$this->title());
    }

    public function rank(): ?array
    {
        return Levels::rank($this->prestige);
    }

    public function xpIntoLevel(): int
    {
        return $this->xp - Levels::totalFor($this->level);
    }

    public function xpForNext(): int
    {
        return Levels::cost($this->level);
    }

    public function percent(): int
    {
        if ($this->isMaxed()) {
            return 100;
        }

        return (int) min(100, floor($this->xpIntoLevel() / $this->xpForNext() * 100));
    }

    public function bonusPercent(): int
    {
        return (int) round((Levels::multiplier($this->prestige) - 1) * 100);
    }

    /** Telt mee voor het held-level: een prestige telt als 50 levels. */
    public function heroLevels(): int
    {
        return $this->prestige * Levels::MAX_LEVEL + $this->level;
    }
}

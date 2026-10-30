<?php

namespace App\Support\Hero;

use App\Enums\HeroClass;

/** Wat een actie aan XP opleverde, en of je daardoor een level omhoog ging. */
final class XpGain
{
    public function __construct(
        public readonly HeroClass $class,
        public readonly int $amount,
        public readonly ClassProgress $before,
        public readonly ClassProgress $after,
    ) {}

    public function leveledUp(): bool
    {
        return $this->after->level > $this->before->level;
    }

    public function toast(): string
    {
        return "+{$this->amount} {$this->class->emoji()} XP";
    }

    /** Payload voor het browser-event "hero-level-up" (feestje in de layout). */
    public function celebration(): array
    {
        return [
            'emoji' => $this->class->emoji(),
            'heading' => "{$this->class->label()} level {$this->after->level}!",
            'text' => $this->after->title() !== $this->before->title()
                ? "Nieuwe titel: {$this->after->fullTitle()}"
                : ($this->after->isMaxed() ? 'Maximaal level! Je kunt nu prestige doen ⭐' : 'Op naar het volgende level.'),
            'big' => $this->after->isMaxed(),
        ];
    }
}

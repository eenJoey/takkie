<?php

namespace App\Livewire\Hero;

use App\Actions\CookMeal;
use App\Actions\PrestigeHeroClass;
use App\Enums\HeroClass;
use App\Exceptions\HeroException;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Models\XpEvent;
use App\Support\CurrentHousehold;
use App\Support\Hero\ClassProgress;
use App\Support\Hero\Hero;
use App\Support\Hero\Levels;
use App\Support\Today;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** "Mijn held": levels per klasse, koken, prestige en het gilde (het hele huishouden). Via het profiel. */
class Index extends Component
{
    /** Gekozen recept bij "Ik heb gekookt"; '' = iets anders / geen recept. */
    public string $recipeId = '';

    public function mount(): void
    {
        $this->recipeId = (string) ($this->menuToday()?->recipe_id ?? '');
    }

    private function householdId(): int
    {
        return app(CurrentHousehold::class)->id();
    }

    #[Computed]
    public function hero(): Hero
    {
        return Hero::for(auth()->user(), $this->householdId());
    }

    #[Computed]
    public function cookedToday(): bool
    {
        return app(CookMeal::class)->cookedToday(auth()->user(), $this->householdId());
    }

    #[Computed]
    public function recipes(): Collection
    {
        return Recipe::orderBy('title')->get(['id', 'title']);
    }

    public function menuToday(): ?MealPlanEntry
    {
        return MealPlanEntry::whereDate('date', Today::iso())->with('recipe')->first();
    }

    /** @return Collection<int, array{user: User, level: int, best: ?ClassProgress}> */
    #[Computed]
    public function guild(): Collection
    {
        return app(CurrentHousehold::class)->get()->users()->orderBy('name')->get()
            ->map(function ($user) {
                $hero = Hero::for($user, $this->householdId());

                return ['user' => $user, 'level' => $hero->level(), 'best' => $hero->best()];
            })
            ->sortByDesc('level')
            ->values();
    }

    #[Computed]
    public function guildLevel(): int
    {
        return Hero::guildLevel($this->householdId());
    }

    #[Computed]
    public function recent(): Collection
    {
        return XpEvent::where('user_id', auth()->id())->latest('id')->limit(8)->get();
    }

    public function cook(CookMeal $cookMeal): void
    {
        $recipe = $this->recipeId !== '' ? Recipe::find((int) $this->recipeId) : null;

        try {
            $gain = $cookMeal->handle(auth()->user(), $this->householdId(), $recipe);
        } catch (HeroException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->hero, $this->cookedToday, $this->guild, $this->guildLevel, $this->recent);
        $this->dispatch('task-completed');
        $this->dispatch('toast', type: 'success', message: "🍳 Lekker gekookt! {$gain->toast()}");

        if ($gain->leveledUp()) {
            $this->dispatch('hero-level-up', ...$gain->celebration());
        }
    }

    public function prestige(string $class, PrestigeHeroClass $prestige): void
    {
        $class = HeroClass::tryFrom($class);

        if (! $class) {
            return;
        }

        try {
            $progress = $prestige->handle(auth()->user(), app(CurrentHousehold::class)->get(), $class);
        } catch (HeroException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->hero, $this->guild);
        $rank = $progress->rank();

        $this->dispatch('hero-level-up',
            emoji: $rank['emoji'],
            heading: "{$rank['label']} {$class->label()}!",
            text: $progress->isLegend()
                ? 'Legende! Je levelt nu eindeloos door, met +'.$progress->bonusPercent().'% XP.'
                : 'Terug naar level 1, met voortaan +'.$progress->bonusPercent().'% XP in deze klasse.',
            big: true,
        );
    }

    public function render(): View
    {
        return view('livewire.hero.index', [
            'menu' => $this->menuToday(),
            'maxLevel' => Levels::MAX_LEVEL,
        ])->title('Mijn held');
    }
}

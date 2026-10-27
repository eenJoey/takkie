<?php

namespace App\Livewire\Recipes;

use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Support\Today;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/** /weekmenu: per dag (maandag t/m zondag) een eigen recept kiezen of zelf iets invullen. Gedeeld met het huishouden. */
#[Title('Weekmenu')]
class WeekMenu extends Component
{
    /** 0 = deze week, 1 = volgende week, -1 = vorige week */
    public int $weekOffset = 0;

    public function previousWeek(): void
    {
        $this->weekOffset--;
    }

    public function nextWeek(): void
    {
        $this->weekOffset++;
    }

    public function thisWeek(): void
    {
        $this->weekOffset = 0;
    }

    /** @return list<Carbon> */
    #[Computed]
    public function days(): array
    {
        $monday = Today::date()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);

        return collect(range(0, 6))->map(fn (int $i) => $monday->copy()->addDays($i))->all();
    }

    #[Computed]
    public function entries(): Collection
    {
        $days = $this->days();

        return MealPlanEntry::query()
            ->with('recipe')
            ->whereBetween('date', [$days[0]->toDateString(), $days[6]->toDateString()])
            ->get()
            ->keyBy(fn (MealPlanEntry $entry) => $entry->date->toDateString());
    }

    #[Computed]
    public function recipes(): Collection
    {
        return Recipe::query()->orderBy('title')->get(['id', 'title', 'slug']);
    }

    public function chooseRecipe(string $date, int $recipeId): void
    {
        $recipe = Recipe::findOrFail($recipeId);

        $this->save($date, ['recipe_id' => $recipe->id, 'title' => $recipe->title]);
    }

    public function chooseFree(string $date, string $title): void
    {
        $title = mb_substr(trim($title), 0, 255);

        if ($title === '') {
            return;
        }

        $this->save($date, ['recipe_id' => null, 'title' => $title]);
    }

    public function clearDay(string $date): void
    {
        MealPlanEntry::whereDate('date', $this->validDate($date))->delete();
        unset($this->entries);
    }

    private function save(string $date, array $values): void
    {
        // whereDate: datumkolommen worden per database net anders opgeslagen (SQLite met tijd erbij)
        $date = $this->validDate($date);
        $entry = MealPlanEntry::whereDate('date', $date)->first() ?? new MealPlanEntry(['date' => $date]);
        $entry->fill([...$values, 'created_by' => auth()->id()])->save();

        unset($this->entries);
    }

    private function validDate(string $date): string
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)), 422);

        return $date;
    }

    public function render(): View
    {
        return view('livewire.recipes.week-menu', ['today' => Today::iso()]);
    }
}

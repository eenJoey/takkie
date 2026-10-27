<?php

namespace App\Livewire\Recipes;

use App\Actions\CopyPublicRecipe;
use App\Actions\SyncRecipeCopy;
use App\Models\Recipe;
use App\Models\Scopes\HouseholdScope;
use App\Support\CurrentHousehold;
use App\Support\IngredientScaler;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /recipes/{slug}: één pagina voor leden én publiek (MIGRATIE-PLAN.md, sectie e).
 * Leden: afvinken, "Reset lijst", "Bewerken" (niet bij een kopie; die kun je wel verwijderen).
 * Anderen: alleen lezen, en alleen als het recept openbaar is (anders 404). Ingelogd: "Toevoegen aan mijn recepten".
 */
class Show extends Component
{
    #[Locked]
    public int $recipeId;

    /** Porties omrekenen: voor hoeveel personen je kookt (alleen weergave; het recept zelf blijft gelijk). */
    public ?int $people = null;

    public function mount(string $slug): void
    {
        $recipe = Recipe::withoutGlobalScope(HouseholdScope::class)->where('slug', $slug)->first();

        // 404 i.p.v. 403: niet verraden dat een privé-recept bestaat
        abort_unless($recipe && Gate::allows('view', $recipe), 404);

        $this->recipeId = $recipe->id;
        $this->people = $recipe->servings;
    }

    private function recipe(): Recipe
    {
        return Recipe::withoutGlobalScope(HouseholdScope::class)->findOrFail($this->recipeId);
    }

    public function morePeople(): void
    {
        $this->people = min(50, ($this->people ?? 1) + 1);
    }

    public function fewerPeople(): void
    {
        $this->people = max(1, ($this->people ?? 1) - 1);
    }

    public function isMember(): bool
    {
        return auth()->check() && auth()->user()->isMemberOf($this->recipe()->household_id);
    }

    public function toggleItem(int $ingredientId): void
    {
        $recipe = $this->recipe();
        $this->authorize('check', $recipe);

        $ingredient = $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->findOrFail($ingredientId);
        $ingredient->update(['is_checked' => ! $ingredient->is_checked]);
    }

    public function resetItems(): void
    {
        $recipe = $this->recipe();
        $this->authorize('check', $recipe);

        $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->update(['is_checked' => false]);
    }

    /** Kopie van dit recept in het huidige huishouden, als die er al is. */
    private function existingCopy(): ?Recipe
    {
        $householdId = app(CurrentHousehold::class)->id();

        return $householdId ? Recipe::withoutGlobalScope(HouseholdScope::class)
            ->where('household_id', $householdId)
            ->where('source_recipe_id', $this->recipeId)
            ->first() : null;
    }

    public function addToMyRecipes(CopyPublicRecipe $copyRecipe): void
    {
        $household = app(CurrentHousehold::class)->get();
        abort_unless($household, 403);

        $copy = $copyRecipe->handle(auth()->user(), $this->recipe(), $household);

        session()->flash('toast', ['type' => 'success', 'message' => 'Toegevoegd aan je recepten']);
        $this->redirectRoute('recipes.show', $copy, navigate: true);
    }

    public function acceptSourceUpdate(SyncRecipeCopy $sync): void
    {
        $recipe = $this->recipe();
        $this->authorize('check', $recipe);

        $sync->accept($recipe);
        $this->dispatch('toast', type: 'success', message: 'Nieuwe versie overgenomen');
    }

    public function dismissSourceUpdate(SyncRecipeCopy $sync): void
    {
        $recipe = $this->recipe();
        $this->authorize('check', $recipe);

        $sync->dismiss($recipe);
        $this->dispatch('toast', type: 'success', message: 'Je houdt je eigen versie');
    }

    public function deleteCopy(): void
    {
        $recipe = $this->recipe();
        $this->authorize('delete', $recipe);

        $recipe->delete();

        session()->flash('toast', ['type' => 'success', 'message' => 'Verwijderd uit je recepten']);
        $this->redirectRoute('recipes.index', navigate: true);
    }

    public function render(): View
    {
        $recipe = $this->recipe();
        $isMember = $this->isMember();

        // leden zien het recept binnen hun eigen huishouden (de scope moet dan het juiste huishouden hebben)
        $useAppLayout = $isMember && app(CurrentHousehold::class)->id() === $recipe->household_id;

        $people = $this->people && $this->people >= 1 && $this->people <= 50 ? $this->people : $recipe->servings;
        $factor = $recipe->servings && $people ? $people / $recipe->servings : 1;

        return view('livewire.recipes.show', [
            'recipe' => $recipe,
            'people' => $people,
            'ingredients' => $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->get()
                ->each(fn ($ingredient) => $ingredient->display = IngredientScaler::scale($ingredient->text, $factor)),
            'tags' => $recipe->tags()->withoutGlobalScope(HouseholdScope::class)->get(),
            'isMember' => $isMember,
            // toevoegen kan alleen ingelogd, met een huishouden, aan een openbaar recept van een ander huishouden
            'canAdd' => ! $isMember && $recipe->isPublic() && auth()->check() && app(CurrentHousehold::class)->id() !== null,
            'existingCopy' => $isMember ? null : $this->existingCopy(),
            'source' => $recipe->isCopy() ? $recipe->source : null,
            'hasUpdate' => $isMember && $recipe->hasSourceUpdate(),
        ])->layout($useAppLayout ? 'layouts::app' : 'layouts::public', [
            'title' => $recipe->title,
            'description' => Str::limit(strip_tags((string) $recipe->instructions), 150),
        ]);
    }
}

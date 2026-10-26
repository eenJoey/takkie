<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Models\Scopes\HouseholdScope;
use App\Support\CurrentHousehold;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * /recipes/{slug}: één pagina voor leden én publiek (MIGRATIE-PLAN.md, sectie e).
 * Leden: afvinken, "Reset lijst", "Bewerken". Anderen: alleen lezen, en alleen als het recept openbaar is (anders 404).
 */
class Show extends Component
{
    #[Locked]
    public int $recipeId;

    public function mount(string $slug): void
    {
        $recipe = Recipe::withoutGlobalScope(HouseholdScope::class)->where('slug', $slug)->first();

        // 404 i.p.v. 403: niet verraden dat een privé-recept bestaat
        abort_unless($recipe && Gate::allows('view', $recipe), 404);

        $this->recipeId = $recipe->id;
    }

    private function recipe(): Recipe
    {
        return Recipe::withoutGlobalScope(HouseholdScope::class)->findOrFail($this->recipeId);
    }

    public function isMember(): bool
    {
        return auth()->check() && auth()->user()->isMemberOf($this->recipe()->household_id);
    }

    public function toggleItem(int $ingredientId): void
    {
        $recipe = $this->recipe();
        $this->authorize('update', $recipe);

        $ingredient = $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->findOrFail($ingredientId);
        $ingredient->update(['is_checked' => ! $ingredient->is_checked]);
    }

    public function resetItems(): void
    {
        $recipe = $this->recipe();
        $this->authorize('update', $recipe);

        $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->update(['is_checked' => false]);
    }

    public function render(): View
    {
        $recipe = $this->recipe();
        $isMember = $this->isMember();

        // leden zien het recept binnen hun eigen huishouden (de scope moet dan het juiste huishouden hebben)
        $useAppLayout = $isMember && app(CurrentHousehold::class)->id() === $recipe->household_id;

        return view('livewire.recipes.show', [
            'recipe' => $recipe,
            'ingredients' => $recipe->ingredients()->withoutGlobalScope(HouseholdScope::class)->get(),
            'tags' => $recipe->tags()->withoutGlobalScope(HouseholdScope::class)->get(),
            'isMember' => $isMember,
        ])->layout($useAppLayout ? 'layouts::app' : 'layouts::public', [
            'title' => $recipe->title,
            'description' => Str::limit(strip_tags((string) $recipe->instructions), 150),
        ]);
    }
}

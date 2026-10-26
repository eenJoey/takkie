<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/** /recipes: receptenboek van het eigen huishouden (port van takkie RecipesIndex). */
#[Title('Receptenboek')]
class Index extends Component
{
    public string $search = '';

    public function render(): View
    {
        $recipes = Recipe::query()
            ->search($this->search)
            ->withCount([
                'ingredients',
                'ingredients as remaining_count' => fn ($query) => $query->where('is_checked', false),
            ])
            ->latest()
            ->latest('id')
            ->get();

        return view('livewire.recipes.index', ['recipes' => $recipes]);
    }
}

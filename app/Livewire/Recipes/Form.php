<?php

namespace App\Livewire\Recipes;

use App\Enums\RecipeVisibility;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Recept aanmaken en bewerken (port van takkie RecipeForm), aangevuld met tags en "Openbaar recept" (M7).
 * Bij bewerken blijft de afvinkstatus behouden voor ingrediënten met dezelfde tekst (takkie verloor die; Q18).
 */
class Form extends Component
{
    #[Locked]
    public ?int $recipeId = null;

    public string $title = '';

    public string $instructions = '';

    public string $newItemText = '';

    /** @var list<string> */
    public array $items = [];

    public string $tags = '';

    public bool $isPublic = false;

    public function mount(?Recipe $recipe = null): void
    {
        if ($recipe?->exists) {
            $this->authorize('update', $recipe);

            $this->recipeId = $recipe->id;
            $this->title = $recipe->title;
            $this->instructions = (string) $recipe->instructions;
            $this->items = $recipe->ingredients->pluck('text')->all();
            $this->tags = $recipe->tags->pluck('name')->implode(', ');
            $this->isPublic = $recipe->isPublic();
        }
    }

    public function addItem(): void
    {
        $text = trim($this->newItemText);

        if ($text !== '') {
            $this->items[] = mb_substr($text, 0, 255);
        }

        $this->reset('newItemText');
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->addItem();

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'items' => ['array', 'max:200'],
            'items.*' => ['string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ], [], ['title' => 'naam', 'instructions' => 'instructies']);

        $recipe = DB::transaction(function () {
            $recipe = $this->recipeId ? Recipe::findOrFail($this->recipeId) : new Recipe(['created_by' => auth()->id()]);

            if ($recipe->exists) {
                $this->authorize('update', $recipe);
            }

            $recipe->title = trim($this->title);
            $recipe->instructions = trim($this->instructions) ?: null;
            $recipe->setVisibility($this->isPublic ? RecipeVisibility::Public : RecipeVisibility::Private);
            $recipe->save();

            $this->syncIngredients($recipe);
            $this->syncTags($recipe);

            return $recipe;
        });

        $this->redirectRoute('recipes.show', $recipe, navigate: true);
    }

    private function syncIngredients(Recipe $recipe): void
    {
        // afvinkstatus per tekst bewaren (dubbele teksten worden op volgorde gekoppeld)
        $checked = $recipe->ingredients()->get()->groupBy('text')->map(fn ($group) => $group->pluck('is_checked')->all())->all();
        $recipe->ingredients()->delete();

        foreach ($this->items as $text) {
            $recipe->ingredients()->create([
                'text' => $text,
                'is_checked' => isset($checked[$text]) && count($checked[$text]) ? (bool) array_shift($checked[$text]) : false,
            ]);
        }
    }

    private function syncTags(Recipe $recipe): void
    {
        $recipe->tags()->delete();

        collect(explode(',', $this->tags))
            ->map(fn (string $tag) => mb_substr(trim($tag), 0, 50))
            ->filter()
            ->unique(fn (string $tag) => Str::slug($tag))
            ->filter(fn (string $tag) => Str::slug($tag) !== '')
            ->take(20)
            ->each(fn (string $tag) => $recipe->tags()->create(['name' => $tag, 'slug' => Str::slug($tag)]));
    }

    public function render(): View
    {
        $recipe = $this->recipeId ? Recipe::find($this->recipeId) : null;

        return view('livewire.recipes.form', ['recipe' => $recipe])
            ->title($recipe ? 'Recept bewerken' : 'Nieuw recept');
    }
}

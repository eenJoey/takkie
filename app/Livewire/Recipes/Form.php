<?php

namespace App\Livewire\Recipes;

use App\Actions\NotifyRecipeCopies;
use App\Enums\RecipeVisibility;
use App\Exceptions\RecipeImportException;
use App\Models\Recipe;
use App\Support\RecipeImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

use function Illuminate\Support\defer;

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

    /** Aantal personen; leeg laten mag (dan geen porties omrekenen). */
    public int|string|null $servings = null;

    public string $newItemText = '';

    /** @var list<string> */
    public array $items = [];

    public string $tags = '';

    public bool $isPublic = false;

    public string $importUrl = '';

    public function mount(?Recipe $recipe = null): void
    {
        if ($recipe?->exists) {
            $this->authorize('update', $recipe);

            $this->recipeId = $recipe->id;
            $this->title = $recipe->title;
            $this->instructions = (string) $recipe->instructions;
            $this->servings = $recipe->servings;
            $this->items = $recipe->ingredients->pluck('text')->all();
            $this->tags = $recipe->tags->pluck('name')->implode(', ');
            $this->isPublic = $recipe->isPublic();
        }
    }

    /** Recept uit een link: vult het formulier, opslaan doet de gebruiker zelf (na controleren). */
    public function importFromUrl(RecipeImporter $importer): void
    {
        $url = trim($this->importUrl);

        if ($url === '') {
            return;
        }

        if (RateLimiter::tooManyAttempts('recipe-import:'.auth()->id(), 10)) {
            $this->dispatch('toast', type: 'error', message: 'Even wachten, probeer het zo opnieuw');

            return;
        }

        RateLimiter::hit('recipe-import:'.auth()->id(), 60);

        try {
            $recipe = $importer->import($url);
        } catch (RecipeImportException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->fillFromImport($recipe, $url);
        $this->reset('importUrl');
    }

    /**
     * "Naar Takkie" (bladwijzer of iPhone-Opdracht): de browser van de gebruiker las het recept uit de pagina
     * en geeft de schema.org Recipe mee. Takkie haalt zelf niets op; het formulier wordt alleen ingevuld.
     */
    public function importFromSchema(mixed $recipe, mixed $url = null): void
    {
        if (! is_array($recipe) || ! isset($recipe['name'])) {
            $this->dispatch('toast', type: 'error', message: 'Geen recept gevonden op deze pagina');

            return;
        }

        $url = is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
        $this->fillFromImport(app(RecipeImporter::class)->fromSchema($recipe), $url);
    }

    private function fillFromImport(array $recipe, ?string $url): void
    {
        $this->title = $recipe['title'] ?: $this->title;
        $this->servings = $recipe['servings'] ?? $this->servings;
        $this->items = $recipe['ingredients'];
        $this->tags = implode(', ', $recipe['tags']);
        $this->instructions = trim($recipe['instructions'].($url ? "\n\nBron: ".mb_substr($url, 0, 500) : ''));

        $this->dispatch('toast', type: 'success', message: 'Recept opgehaald. Controleer het en sla op.');
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
            'servings' => ['nullable', 'integer', 'min:1', 'max:50'],
            'items' => ['array', 'max:200'],
            'items.*' => ['string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
        ], [], ['title' => 'naam', 'instructions' => 'instructies', 'servings' => 'aantal personen']);

        $changed = false;

        $recipe = DB::transaction(function () use (&$changed) {
            $recipe = $this->recipeId ? Recipe::findOrFail($this->recipeId) : new Recipe(['created_by' => auth()->id()]);

            if ($recipe->exists) {
                $this->authorize('update', $recipe);
            }

            $before = $recipe->exists ? $this->contentOf($recipe) : null;

            $recipe->title = trim($this->title);
            $recipe->instructions = trim($this->instructions) ?: null;
            $recipe->servings = filled($this->servings) ? (int) $this->servings : null;
            $recipe->setVisibility($this->isPublic ? RecipeVisibility::Public : RecipeVisibility::Private);
            $recipe->save();

            $this->syncIngredients($recipe);
            $this->syncTags($recipe);

            // wie een kopie heeft, krijgt dan een melding dat er een nieuwe versie is (Recipe::hasSourceUpdate)
            if ($before !== null && $before !== $this->contentOf($recipe->fresh())) {
                $recipe->update(['content_changed_at' => now()]);
                $changed = true;
            }

            return $recipe;
        });

        if ($changed) {
            // pushmeldingen pas na het antwoord versturen, zodat opslaan niet wacht op de pushdiensten
            $editorId = auth()->id();
            defer(fn () => app(NotifyRecipeCopies::class)->handle($recipe->fresh(), $editorId));
        }

        $this->redirectRoute('recipes.show', $recipe, navigate: true);
    }

    /** Inhoud die een kopie overneemt; afvinken en zichtbaarheid tellen niet mee. */
    private function contentOf(Recipe $recipe): array
    {
        return [
            $recipe->title,
            $recipe->instructions,
            $recipe->servings,
            $recipe->ingredients()->pluck('text')->all(),
            $recipe->tags()->pluck('name')->all(),
        ];
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

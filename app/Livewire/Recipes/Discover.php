<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Support\CurrentHousehold;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * /recipes/discover: openbare recepten van alle huishoudens doorzoeken (M7). Ook zonder account.
 * Zoeken op titel, ingrediënt of tag; filteren op tag via ?tag=.
 */
class Discover extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $tag = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearTag(): void
    {
        $this->tag = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $tagSlug = Str::slug($this->tag);

        $recipes = Recipe::publicQuery()
            ->search($this->search)
            ->when($tagSlug !== '', fn ($query) => $query->whereHas('tags', fn ($q) => $q->withoutGlobalScopes()->where('slug', $tagSlug)))
            ->withCount(['ingredients' => fn ($q) => $q->withoutGlobalScopes()])
            ->with(['tags' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        $hasHousehold = auth()->check() && app(CurrentHousehold::class)->get() !== null;

        return view('livewire.recipes.discover', ['recipes' => $recipes])
            ->layout($hasHousehold ? 'layouts::app' : 'layouts::public', [
                'title' => 'Recepten ontdekken',
                'description' => 'Openbare recepten van Takkie-huishoudens: zoek op naam, ingrediënt of tag.',
            ]);
    }
}

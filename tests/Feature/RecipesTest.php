<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Enums\RecipeVisibility;
use App\Livewire\Recipes\Discover;
use App\Livewire\Recipes\Form;
use App\Livewire\Recipes\Index;
use App\Livewire\Recipes\Show;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Receptenboek (M6) en openbare recepten met zoeken (M7). */
class RecipesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->household = app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
    }

    /** Recept in een (ander) huishouden aanmaken, buiten de huidige scope om. */
    private function recipeIn(Household $household, array $attributes = [], array $ingredients = [], array $tags = []): Recipe
    {
        return app(CurrentHousehold::class)->runAs($household, function () use ($attributes, $ingredients, $tags) {
            $recipe = Recipe::create(['title' => 'Pasta pesto', ...$attributes]);
            if (($attributes['visibility'] ?? null) === RecipeVisibility::Public) {
                $recipe->update(['published_at' => now()]);
            }
            foreach ($ingredients as $text) {
                $recipe->ingredients()->create(['text' => $text]);
            }
            foreach ($tags as $tag) {
                $recipe->tags()->create(['name' => $tag, 'slug' => str($tag)->slug()]);
            }

            return $recipe;
        });
    }

    private function otherHousehold(): Household
    {
        $household = app(CreateHousehold::class)->handle(User::factory()->create(), 'Buren');
        $this->actingAs($this->user);
        app(CurrentHousehold::class)->forget();

        return $household;
    }

    public function test_create_recipe_with_items_instructions_and_tags(): void
    {
        Livewire::test(Form::class)
            ->set('title', 'Pasta pesto')
            ->set('newItemText', 'Pasta')
            ->call('addItem')
            ->set('newItemText', 'Pesto')
            ->call('addItem')
            ->set('instructions', "Kook de pasta.\nRoer de pesto erdoor.")
            ->set('tags', 'Vegetarisch, snel, vegetarisch')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('recipes.show', 'pasta-pesto'));

        $recipe = Recipe::sole();
        $this->assertSame('pasta-pesto', $recipe->slug);
        $this->assertSame(RecipeVisibility::Private, $recipe->visibility);
        $this->assertSame(['Pasta', 'Pesto'], $recipe->ingredients->pluck('text')->all());
        $this->assertSame(['snel', 'vegetarisch'], $recipe->tags->pluck('slug')->sort()->values()->all());
    }

    public function test_slugs_are_unique_and_avoid_reserved_paths(): void
    {
        $first = Recipe::create(['title' => 'Soep']);
        $second = Recipe::create(['title' => 'Soep']);
        $reserved = Recipe::create(['title' => 'Discover']);

        $this->assertSame('soep', $first->slug);
        $this->assertSame('soep-2', $second->slug);
        $this->assertSame('discover-recept', $reserved->slug);
    }

    public function test_index_shows_own_recipes_with_counts_and_search(): void
    {
        $recipe = $this->recipeIn($this->household, ['title' => 'Pasta pesto'], ['Pasta', 'Pesto', 'Kaas']);
        $recipe->ingredients()->first()->update(['is_checked' => true]);
        $this->recipeIn($this->household, ['title' => 'Tomatensoep'], ['Tomaten']);
        $this->recipeIn($this->otherHousehold(), ['title' => 'Andermans taart']);

        Livewire::test(Index::class)
            ->assertSee('Pasta pesto')
            ->assertSee('3 items')
            ->assertSee('2 open')
            ->assertSee('Tomatensoep')
            ->assertDontSee('Andermans taart')
            ->set('search', 'kaas')
            ->assertSee('Pasta pesto')
            ->assertDontSee('Tomatensoep');
    }

    public function test_member_can_check_items_and_reset_the_list(): void
    {
        $recipe = $this->recipeIn($this->household, [], ['Pasta', 'Pesto']);
        $pasta = $recipe->ingredients()->first();

        $this->get(route('recipes.show', $recipe->slug))->assertOk()->assertSee('Boodschappenlijstje')->assertSee('Bewerken');

        $page = Livewire::test(Show::class, ['slug' => $recipe->slug])->call('toggleItem', $pasta->id);
        $this->assertTrue($pasta->fresh()->is_checked);

        $page->call('resetItems');
        $this->assertFalse($pasta->fresh()->is_checked);
    }

    public function test_editing_keeps_checked_state_of_unchanged_items(): void
    {
        $recipe = $this->recipeIn($this->household, [], ['Pasta', 'Pesto']);
        $recipe->ingredients()->where('text', 'Pasta')->update(['is_checked' => true]);

        Livewire::test(Form::class, ['recipe' => $recipe])
            ->assertSet('items', ['Pasta', 'Pesto'])
            ->set('newItemText', 'Parmezaan')
            ->call('addItem')
            ->call('removeItem', 1)
            ->call('save');

        $items = $recipe->fresh()->ingredients->mapWithKeys(fn ($i) => [$i->text => $i->is_checked])->all();
        $this->assertSame(['Pasta' => true, 'Parmezaan' => false], $items);
    }

    public function test_making_a_recipe_public_sets_published_at(): void
    {
        $recipe = $this->recipeIn($this->household);

        Livewire::test(Form::class, ['recipe' => $recipe])->set('isPublic', true)->call('save');

        $recipe->refresh();
        $this->assertTrue($recipe->isPublic());
        $this->assertNotNull($recipe->published_at);
    }

    public function test_private_recipe_of_another_household_is_a_404(): void
    {
        $foreign = $this->recipeIn($this->otherHousehold(), ['title' => 'Geheim recept']);

        $this->get(route('recipes.show', $foreign->slug))->assertNotFound();
        $this->get(route('recipes.edit', $foreign->slug))->assertNotFound();

        auth()->logout();
        $this->get(route('recipes.show', $foreign->slug))->assertNotFound();
    }

    public function test_public_recipe_is_readable_by_guests_but_not_editable(): void
    {
        $recipe = $this->recipeIn($this->otherHousehold(), ['title' => 'Openbare lasagne', 'visibility' => RecipeVisibility::Public], ['Lasagnebladen'], ['Italiaans']);
        $item = RecipeIngredient::withoutGlobalScopes()->where('recipe_id', $recipe->id)->first();

        auth()->logout();
        app(CurrentHousehold::class)->forget();

        $this->get(route('recipes.show', $recipe->slug))
            ->assertOk()
            ->assertSee('Openbare lasagne')
            ->assertSee('Lasagnebladen')
            ->assertSee('#Italiaans')
            ->assertSee('Inloggen')
            ->assertDontSee('Bewerken')
            ->assertDontSee('Reset lijst');

        Livewire::test(Show::class, ['slug' => $recipe->slug])->call('toggleItem', $item->id)->assertForbidden();
        $this->assertFalse($item->fresh()->is_checked);
    }

    public function test_logged_in_non_member_can_read_public_recipe_but_not_check_items(): void
    {
        $recipe = $this->recipeIn($this->otherHousehold(), ['visibility' => RecipeVisibility::Public], ['Basilicum']);
        $item = RecipeIngredient::withoutGlobalScopes()->where('recipe_id', $recipe->id)->first();

        $this->get(route('recipes.show', $recipe->slug))->assertOk()->assertSee('Basilicum')->assertDontSee('Reset lijst');
        Livewire::test(Show::class, ['slug' => $recipe->slug])->call('resetItems')->assertForbidden();
        $this->get(route('recipes.edit', $recipe->slug))->assertNotFound();
        $this->assertFalse($item->fresh()->is_checked);
    }

    public function test_discover_searches_public_recipes_on_title_ingredient_and_tag(): void
    {
        $buren = $this->otherHousehold();
        $this->recipeIn($buren, ['title' => 'Groene curry', 'visibility' => RecipeVisibility::Public], ['Kokosmelk'], ['Thais']);
        $this->recipeIn($buren, ['title' => 'Pannenkoeken', 'visibility' => RecipeVisibility::Public], ['Bloem', 'Melk']);
        $this->recipeIn($buren, ['title' => 'Geheime saus'], ['Kokosmelk']);
        $this->recipeIn($this->household, ['title' => 'Mijn privé curry'], ['Kokosmelk']);

        auth()->logout();
        app(CurrentHousehold::class)->forget();

        $this->get(route('recipes.discover'))->assertOk()->assertSee('Groene curry')->assertSee('Pannenkoeken')->assertDontSee('Geheime saus');

        Livewire::test(Discover::class)->set('search', 'kokos')->assertSee('Groene curry')->assertDontSee('Pannenkoeken')->assertDontSee('Mijn privé curry');
        Livewire::test(Discover::class)->set('search', 'pannenkoek')->assertSee('Pannenkoeken')->assertDontSee('Groene curry');
        Livewire::test(Discover::class)->set('search', 'thais')->assertSee('Groene curry')->assertDontSee('Pannenkoeken');
        Livewire::test(Discover::class)->set('tag', 'thais')->assertSee('Groene curry')->assertDontSee('Pannenkoeken');
        Livewire::test(Discover::class)->set('search', '100%')->assertDontSee('Groene curry');
    }

    public function test_recipe_pages_require_a_household_except_public_ones(): void
    {
        auth()->logout();

        $this->get(route('recipes.index'))->assertRedirect('/login');
        $this->get(route('recipes.create'))->assertRedirect('/login');
        $this->get(route('recipes.discover'))->assertOk();
    }

    public function test_public_recipe_can_be_added_as_a_read_only_copy(): void
    {
        $source = $this->recipeIn($this->otherHousehold(), ['title' => 'Lasagne', 'instructions' => 'Laagjes maken', 'visibility' => RecipeVisibility::Public], ['Pasta', 'Saus'], ['Italiaans']);

        Livewire::test(Show::class, ['slug' => $source->slug])
            ->assertSee('Toevoegen aan mijn recepten')
            ->call('addToMyRecipes')
            ->assertRedirect();

        $copy = Recipe::where('source_recipe_id', $source->id)->sole();
        $this->assertSame($this->household->id, $copy->household_id);
        $this->assertTrue($copy->isCopy());
        $this->assertFalse($copy->isPublic());
        $this->assertSame('Laagjes maken', $copy->instructions);
        $this->assertSame(['Pasta', 'Saus'], $copy->ingredients->pluck('text')->all());
        $this->assertSame(['Italiaans'], $copy->tags->pluck('name')->all());

        // nog een keer toevoegen maakt geen tweede kopie
        Livewire::test(Show::class, ['slug' => $source->slug])->assertSee('Staat al in je recepten')->call('addToMyRecipes');
        $this->assertSame(1, Recipe::where('source_recipe_id', $source->id)->count());

        // alleen-lezen: geen bewerken, wel afvinken
        $this->get(route('recipes.edit', $copy))->assertForbidden();
        Livewire::test(Show::class, ['slug' => $copy->slug])
            ->assertSee('Kopie van een openbaar recept')
            ->assertDontSee('Bewerken')
            ->call('toggleItem', $copy->ingredients->first()->id);
        $this->assertTrue($copy->ingredients()->first()->is_checked);
        $this->get(route('recipes.index'))->assertSee('Kopie');
    }

    public function test_copy_stays_when_the_original_is_deleted(): void
    {
        $source = $this->recipeIn($this->otherHousehold(), ['title' => 'Lasagne', 'visibility' => RecipeVisibility::Public], ['Pasta']);
        $copy = app(\App\Actions\CopyPublicRecipe::class)->handle($this->user, $source, $this->household);

        Recipe::withoutGlobalScopes()->whereKey($source->id)->delete();

        $copy = Recipe::find($copy->id);
        $this->assertNotNull($copy);
        $this->assertNull($copy->source_recipe_id);
        $this->assertTrue($copy->isCopy());
        $this->get(route('recipes.show', $copy))->assertOk()
            ->assertSee('Lasagne')
            ->assertSee('Pasta')
            ->assertSee('Het originele recept is verwijderd');
        $this->get(route('recipes.edit', $copy))->assertForbidden();
    }

    public function test_only_public_recipes_of_another_household_can_be_copied_and_copies_can_be_deleted(): void
    {
        $private = $this->recipeIn($this->otherHousehold(), ['title' => 'Geheim']);
        $own = $this->recipeIn($this->household, ['title' => 'Eigen', 'visibility' => RecipeVisibility::Public]);
        $copyAction = app(\App\Actions\CopyPublicRecipe::class);

        foreach ([$private, $own] as $recipe) {
            try {
                $copyAction->handle($this->user, $recipe, $this->household);
                $this->fail('Kopiëren had geweigerd moeten worden');
            } catch (\Illuminate\Auth\Access\AuthorizationException) {
            }
        }

        Livewire::test(Show::class, ['slug' => $own->slug])->assertDontSee('Toevoegen aan mijn recepten')->call('deleteCopy')->assertForbidden();

        $public = $this->recipeIn($this->otherHousehold(), ['title' => 'Soep', 'visibility' => RecipeVisibility::Public]);
        $copy = $copyAction->handle($this->user, $public, $this->household);
        Livewire::test(Show::class, ['slug' => $copy->slug])->call('deleteCopy')->assertRedirect(route('recipes.index'));
        $this->assertModelMissing($copy);
        $this->assertModelExists($public);
    }

    public function test_copies_get_a_notice_when_the_original_changes_and_can_take_it_over(): void
    {
        $neighbours = $this->otherHousehold();
        $owner = $neighbours->users()->first();
        $source = $this->recipeIn($neighbours, ['title' => 'Lasagne', 'visibility' => RecipeVisibility::Public], ['Pasta', 'Saus']);
        $copy = app(\App\Actions\CopyPublicRecipe::class)->handle($this->user, $source, $this->household);
        $copy->ingredients()->where('text', 'Pasta')->update(['is_checked' => true]);

        $this->assertFalse($copy->fresh()->hasSourceUpdate());

        // alleen opslaan zonder wijziging: geen melding
        $this->actingAs($owner);
        Livewire::test(Form::class, ['recipe' => $source])->call('save');
        $this->assertNull($source->fresh()->content_changed_at);

        $this->travel(1)->minutes();
        Livewire::test(Form::class, ['recipe' => $source])->set('title', 'Lasagne de luxe')->set('newItemText', 'Kaas')->call('save');
        $this->assertNotNull($source->fresh()->content_changed_at);

        $this->actingAs($this->user);
        $this->assertTrue($copy->fresh()->hasSourceUpdate());
        $this->get(route('recipes.index'))->assertSee('Nieuwe versie');
        Livewire::test(Show::class, ['slug' => $copy->slug])
            ->assertSee('Het originele recept is aangepast.')
            ->call('acceptSourceUpdate')
            ->assertDispatched('toast', type: 'success', message: 'Nieuwe versie overgenomen');

        $copy = $copy->fresh();
        $this->assertSame('Lasagne de luxe', $copy->title);
        $this->assertSame(['Pasta', 'Saus', 'Kaas'], $copy->ingredients->pluck('text')->all());
        $this->assertSame([true, false, false], $copy->ingredients->pluck('is_checked')->all());
        $this->assertFalse($copy->hasSourceUpdate());
        $this->assertTrue($copy->isCopy());
    }

    public function test_a_change_can_be_ignored_and_the_next_change_notifies_again(): void
    {
        $neighbours = $this->otherHousehold();
        $owner = $neighbours->users()->first();
        $source = $this->recipeIn($neighbours, ['title' => 'Soep', 'visibility' => RecipeVisibility::Public], ['Water']);
        $copy = app(\App\Actions\CopyPublicRecipe::class)->handle($this->user, $source, $this->household);

        $this->travel(1)->minutes();
        $this->actingAs($owner);
        Livewire::test(Form::class, ['recipe' => $source])->set('title', 'Tomatensoep')->call('save');

        $this->actingAs($this->user);
        Livewire::test(Show::class, ['slug' => $copy->slug])->call('dismissSourceUpdate');
        $this->assertSame('Soep', $copy->fresh()->title);
        $this->assertFalse($copy->fresh()->hasSourceUpdate());

        $this->travel(1)->minutes();
        $this->actingAs($owner);
        Livewire::test(Form::class, ['recipe' => $source])->set('instructions', 'Koken')->call('save');
        $this->actingAs($this->user);
        $this->assertTrue($copy->fresh()->hasSourceUpdate());

        // origineel weer privé: geen melding meer
        Recipe::withoutGlobalScopes()->whereKey($source->id)->update(['visibility' => RecipeVisibility::Private]);
        $this->assertFalse($copy->fresh()->hasSourceUpdate());
    }
}

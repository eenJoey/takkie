<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Livewire\Recipes\WeekMenu;
use App\Livewire\Today;
use App\Models\Household;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Support\CurrentHousehold;
use App\Support\Today as TodayDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeekMenuTest extends TestCase
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

    public function test_week_shows_monday_to_sunday_and_can_be_browsed(): void
    {
        $this->get(route('weekmenu'))->assertOk()->assertSee('Weekmenu')->assertSee('maandag')->assertSee('zondag');

        $component = Livewire::test(WeekMenu::class);
        $monday = $component->instance()->days()[0];
        $this->assertTrue($monday->isMonday());
        $this->assertTrue($monday->lte(TodayDate::date()) && $monday->copy()->addDays(6)->gte(TodayDate::date()));

        $component->call('nextWeek')->assertSee('Naar deze week');
        $this->assertTrue($component->instance()->days()[0]->eq($monday->copy()->addWeek()));
    }

    public function test_a_day_gets_an_own_recipe_or_free_text_and_can_be_cleared(): void
    {
        $recipe = Recipe::create(['title' => 'Lasagne']);
        $today = TodayDate::iso();
        $tomorrow = TodayDate::date()->addDay()->toDateString();

        Livewire::test(WeekMenu::class)
            ->assertSee('Lasagne')
            ->call('chooseRecipe', $today, $recipe->id)
            ->call('chooseFree', $tomorrow, '  Friet halen ')
            ->assertSee('Friet halen');

        $this->assertSame(2, MealPlanEntry::count());
        $this->assertSame($recipe->id, MealPlanEntry::whereDate('date', $today)->value('recipe_id'));

        // zelfde dag opnieuw kiezen vervangt
        Livewire::test(WeekMenu::class)->call('chooseFree', $today, 'Pizza');
        $this->assertSame(2, MealPlanEntry::count());
        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('recipe_id'));

        Livewire::test(Today::class)->assertSee('Pizza');

        Livewire::test(WeekMenu::class)->call('clearDay', $today);
        $this->assertSame(1, MealPlanEntry::count());
    }

    public function test_only_own_recipes_and_valid_dates(): void
    {
        $other = app(CreateHousehold::class)->handle(User::factory()->create(), 'Buren');
        $foreign = app(CurrentHousehold::class)->runAs($other, fn () => Recipe::create(['title' => 'Geheim']));
        $this->actingAs($this->user);

        Livewire::test(WeekMenu::class)->assertDontSee('Geheim')->call('chooseRecipe', TodayDate::iso(), $foreign->id)->assertNotFound();
        Livewire::test(WeekMenu::class)->call('chooseFree', '2026-02-30', 'x')->assertStatus(422);
        $this->assertSame(0, MealPlanEntry::withoutGlobalScopes()->count());
    }
}

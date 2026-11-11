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

    public function test_a_household_member_can_be_chosen_as_cook_and_reset_to_nobody(): void
    {
        $partner = User::factory()->create(['name' => 'Sanne']);
        $this->household->addMember($partner);
        $today = TodayDate::iso();

        Livewire::test(WeekMenu::class)
            ->call('chooseFree', $today, 'Pizza')
            ->assertSee('Sanne kookt')
            ->call('chooseCook', $today, $partner->id)
            ->assertHasNoErrors();

        $this->assertSame($partner->id, MealPlanEntry::whereDate('date', $today)->value('cook_id'));

        // blijft staan na herladen, ook als het gerecht wordt vervangen
        Livewire::test(WeekMenu::class)->call('chooseFree', $today, 'Soep');
        $this->assertSame($partner->id, MealPlanEntry::whereDate('date', $today)->value('cook_id'));
        $this->assertSame($partner->id, Livewire::test(WeekMenu::class)->instance()->entries()->get($today)->cook->id);

        Livewire::test(WeekMenu::class)->call('chooseCook', $today, null);
        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('cook_id'));

        Livewire::test(WeekMenu::class)->call('clearDay', $today);
        $this->assertSame(0, MealPlanEntry::count());
    }

    public function test_cook_must_be_member_of_own_household_and_day_must_have_a_meal(): void
    {
        $stranger = User::factory()->create();
        app(CreateHousehold::class)->handle($stranger, 'Buren');
        $this->actingAs($this->user);
        $today = TodayDate::iso();

        // dag zonder gerecht: geen losse kok
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, $this->user->id)->assertStatus(422);
        $this->assertSame(0, MealPlanEntry::count());

        Livewire::test(WeekMenu::class)
            ->call('chooseFree', $today, 'Pizza')
            ->assertDontSee($stranger->name.' kookt')
            ->call('chooseCook', $today, $stranger->id)
            ->assertStatus(422);

        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('cook_id'));
    }

    public function test_cook_is_shown_on_today_and_hero(): void
    {
        Livewire::test(WeekMenu::class)->call('chooseFree', TodayDate::iso(), 'Pizza');
        Livewire::test(Today::class)->assertSee('Pizza')->assertDontSee('kookt');

        $this->user->update(['name' => 'Joris']);
        Livewire::test(WeekMenu::class)->call('chooseCook', TodayDate::iso(), $this->user->id);

        Livewire::test(Today::class)->assertSee('Joris kookt');
        $this->get(route('hero'))->assertOk()->assertSee('Pizza, Joris kookt');
    }
}

<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Livewire\Household\Index as HouseholdPage;
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
        $stranger = User::factory()->create(['name' => 'Buurman']);
        app(CreateHousehold::class)->handle($stranger, 'Buren');
        $this->actingAs($this->user);
        $today = TodayDate::iso();

        // dag zonder gerecht: geen losse kok
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, $this->user->id)->assertStatus(422);
        $this->assertSame(0, MealPlanEntry::count());

        $component = Livewire::test(WeekMenu::class)->call('chooseFree', $today, 'Pizza');
        $this->assertNotContains($stranger->id, $component->instance()->members()->pluck('id'));
        $component->call('chooseCook', $today, $stranger->id)->assertStatus(422);
        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('cook_id'));
    }

    public function test_invalid_cook_or_date_gives_422_not_500(): void
    {
        $today = TodayDate::iso();
        Livewire::test(WeekMenu::class)->call('chooseFree', $today, 'Pizza');

        Livewire::test(WeekMenu::class)->call('chooseCook', $today, 999999)->assertStatus(422);
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, 'abc')->assertStatus(422);
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, -1)->assertStatus(422);
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, ['x'])->assertStatus(422);
        Livewire::test(WeekMenu::class)->call('chooseCook', '2026-02-30', $this->user->id)->assertStatus(422);
        Livewire::test(WeekMenu::class)->call('chooseCook', 'morgen', $this->user->id)->assertStatus(422);
        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('cook_id'));

        // id als string (zoals uit een select) en '' als niemand werken wel
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, (string) $this->user->id);
        $this->assertSame($this->user->id, MealPlanEntry::whereDate('date', $today)->value('cook_id'));
        Livewire::test(WeekMenu::class)->call('chooseCook', $today, '');
        $this->assertNull(MealPlanEntry::whereDate('date', $today)->value('cook_id'));
    }

    public function test_cook_is_cleared_when_member_leaves_household(): void
    {
        $partner = User::factory()->create(['name' => 'Sanne']);
        $this->household->addMember($partner);
        $today = TodayDate::iso();
        $tomorrow = TodayDate::date()->addDay()->toDateString();

        Livewire::test(WeekMenu::class)
            ->call('chooseFree', $today, 'Pizza')
            ->call('chooseFree', $tomorrow, 'Soep')
            ->call('chooseCook', $today, $partner->id)
            ->call('chooseCook', $tomorrow, $this->user->id);

        // kok in een ander huishouden van het ex-lid blijft staan
        $own = app(CreateHousehold::class)->handle($partner, 'Eigen huis');
        $elsewhere = app(CurrentHousehold::class)->runAs($own, fn () => MealPlanEntry::create(['date' => $today, 'title' => 'Wraps', 'cook_id' => $partner->id]));

        // verlaten gebeurt vanuit het gedeelde huishouden
        $partner = $partner->fresh();
        $partner->forceFill(['current_household_id' => $this->household->id])->save();
        $this->actingAs($partner);
        app(CurrentHousehold::class)->forget();
        Livewire::test(HouseholdPage::class)->call('leave')->assertRedirect('/');

        $this->assertFalse($partner->isMemberOf($this->household));
        $this->assertNull(MealPlanEntry::withoutGlobalScopes()->where('household_id', $this->household->id)->whereDate('date', $today)->value('cook_id'));
        $this->assertSame($this->user->id, MealPlanEntry::withoutGlobalScopes()->where('household_id', $this->household->id)->whereDate('date', $tomorrow)->value('cook_id'));
        $this->assertSame($partner->id, $elsewhere->fresh()->cook_id);

        $this->actingAs($this->user);
        app(CurrentHousehold::class)->forget();
        Livewire::test(Today::class)->assertSee('Pizza')->assertDontSee('Sanne kookt');
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

<?php

namespace Tests\Feature;

use App\Actions\CompleteTask;
use App\Actions\CookMeal;
use App\Actions\CreateHousehold;
use App\Actions\PrestigeHeroClass;
use App\Enums\HeroClass;
use App\Enums\ListType;
use App\Enums\RecurrenceType;
use App\Exceptions\HeroException;
use App\Livewire\Hero\Index;
use App\Livewire\Tasks\Board;
use App\Models\HeroPrestige;
use App\Models\Household;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\User;
use App\Models\XpEvent;
use App\Notifications\HeroPrestiged;
use App\Support\Hero\ClassProgress;
use App\Support\Hero\Hero;
use App\Support\Hero\Levels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** "Mijn held": XP per klasse door taken en koken, levels en prestige. */
class HeroTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    private TodoList $tasksList;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-30 18:00', 'Europe/Amsterdam'));

        $this->user = User::factory()->create(['name' => 'Joey']);
        $this->household = app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
        $this->tasksList = TodoList::where('type', ListType::Tasks)->sole();
    }

    private function task(array $attributes = []): Task
    {
        return $this->tasksList->tasks()->create([
            'title' => 'Stofzuigen',
            'points' => 10,
            'hero_class' => HeroClass::Cleaner,
            'recurrence_type' => RecurrenceType::Daily,
            'recurrence_interval' => 1,
            'due_date' => '2026-10-30',
            ...$attributes,
        ]);
    }

    private function hero(?User $user = null): Hero
    {
        return Hero::for($user ?? $this->user, $this->household->id);
    }

    public function test_completing_a_task_gives_xp_in_its_class_with_a_minimum_of_ten(): void
    {
        $complete = app(CompleteTask::class);
        $complete->handle($this->user, $this->task(['points' => 3]));
        $this->assertSame(10, $complete->xpGain->amount);

        $complete->handle($this->user, $this->task(['points' => 25]));

        $this->assertSame(35, $this->hero()->get(HeroClass::Cleaner)->earned);
        $this->assertSame(0, $this->hero()->get(HeroClass::Chef)->earned);
    }

    public function test_tasks_without_a_class_give_no_xp(): void
    {
        $complete = app(CompleteTask::class);
        $complete->handle($this->user, $this->task(['hero_class' => null]));

        $this->assertNull($complete->xpGain);
        $this->assertSame(0, XpEvent::count());
    }

    public function test_board_shows_xp_and_celebrates_a_level_up(): void
    {
        $task = $this->task();

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('complete', $task->id)
            ->assertDispatched('toast', type: 'success', message: '🎉 +10 punten! · +10 🧹 XP')
            ->assertNotDispatched('hero-level-up');

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('complete', $task->id)
            ->assertDispatched('hero-level-up', heading: 'Schoonmaker level 2!');
    }

    public function test_the_task_form_saves_and_suggests_a_class(): void
    {
        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('openNew')
            ->set('form.title', 'Gras maaien')
            ->assertSet('form.heroClass', 'gardener')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(HeroClass::Gardener, Task::where('title', 'Gras maaien')->sole()->hero_class);

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('openNew')
            ->set('form.title', 'Iets')
            ->set('form.heroClass', 'wizard')
            ->call('save')
            ->assertHasErrors('form.heroClass');
    }

    public function test_cooking_gives_chef_xp_once_a_day(): void
    {
        $cook = app(CookMeal::class);

        $this->assertSame(10, $cook->handle($this->user, $this->household->id)->amount);

        $this->expectException(HeroException::class);
        $cook->handle($this->user, $this->household->id);
    }

    public function test_cooking_a_new_recipe_or_the_week_menu_gives_extra_xp(): void
    {
        $recipe = Recipe::create(['title' => 'Lasagne', 'created_by' => $this->user->id]);
        $cook = app(CookMeal::class);

        // eerste keer dit recept
        $this->assertSame(20, $cook->handle($this->user, $this->household->id, $recipe)->amount);

        // een dag later, staat op het weekmenu
        Carbon::setTestNow(Carbon::parse('2026-10-31 18:00', 'Europe/Amsterdam'));
        MealPlanEntry::create(['date' => '2026-10-31', 'recipe_id' => $recipe->id, 'title' => 'Lasagne', 'created_by' => $this->user->id]);
        $this->assertSame(15, $cook->handle($this->user, $this->household->id, $recipe)->amount);

        // weer een dag later, niet op het menu
        Carbon::setTestNow(Carbon::parse('2026-11-01 18:00', 'Europe/Amsterdam'));
        $this->assertSame(10, $cook->handle($this->user, $this->household->id, $recipe)->amount);

        $this->assertSame(45, $this->hero()->get(HeroClass::Chef)->earned);
    }

    public function test_hero_page_cooks_and_blocks_a_second_time(): void
    {
        $this->get(route('hero'))->assertOk()->assertSee('Mijn held')->assertSee('Ik heb gekookt');

        Livewire::test(Index::class)
            ->call('cook')
            ->assertDispatched('toast', type: 'success')
            ->assertSee('Vandaag al gekookt');

        Livewire::test(Index::class)->call('cook')->assertDispatched('toast', type: 'error');
        $this->assertSame(1, XpEvent::count());
    }

    public function test_prestige_needs_level_50_resets_and_adds_ten_percent(): void
    {
        $this->expectsPrestigeFailureFirst();

        XpEvent::create([
            'user_id' => $this->user->id, 'hero_class' => HeroClass::Chef, 'amount' => Levels::prestigeCost() + 5,
            'source' => XpEvent::SOURCE_TASK, 'date' => '2026-10-30', 'created_at' => now(),
        ]);
        $this->assertSame(50, $this->hero()->get(HeroClass::Chef)->level);

        Livewire::test(Index::class)
            ->assertSee('Prestige')
            ->call('prestige', 'chef')
            ->assertDispatched('hero-level-up', heading: 'Brons Chef!');

        $chef = $this->hero()->get(HeroClass::Chef);
        $this->assertSame(1, $chef->prestige);
        $this->assertSame(1, $chef->level);
        $this->assertSame(5, $chef->xp);
        // prestige telt als 50 levels: 50 + 1, plus 5 andere klassen op level 1
        $this->assertSame(56, $this->hero()->level());

        // +10% XP in die klasse
        $this->assertSame(11, app(CookMeal::class)->handle($this->user, $this->household->id)->amount);
    }

    private function expectsPrestigeFailureFirst(): void
    {
        try {
            app(PrestigeHeroClass::class)->handle($this->user, $this->household, HeroClass::Chef);
            $this->fail('Prestige zonder level 50 mag niet');
        } catch (HeroException) {
            $this->assertSame(0, HeroPrestige::count());
        }
    }

    public function test_prestige_notifies_the_rest_of_the_household(): void
    {
        Notification::fake();
        $sanne = User::factory()->create();
        $this->household->addMember($sanne);
        $sanne->updatePushSubscription('https://push.example/sanne', 'key', 'token');

        $progress = new ClassProgress(HeroClass::Chef, Levels::prestigeCost(), 1, Levels::prestigeCost());

        $sent = app(PrestigeHeroClass::class)->notifyHousehold($this->user, $this->household, $progress);

        $this->assertSame(1, $sent);
        Notification::assertSentTo($sanne, HeroPrestiged::class, fn (HeroPrestiged $n) => $n->title === '🥉 Brons Chef');
        Notification::assertNotSentTo($this->user, HeroPrestiged::class);
    }

    public function test_xp_stays_within_the_household(): void
    {
        app(CompleteTask::class)->handle($this->user, $this->task());

        $other = app(CreateHousehold::class)->handle($this->user, 'Werk');

        $this->assertSame(0, Hero::for($this->user, $other->id)->get(HeroClass::Cleaner)->earned);
        $this->assertSame(10, $this->hero()->get(HeroClass::Cleaner)->earned);
    }

    public function test_profile_links_to_the_hero_page(): void
    {
        $this->get(route('profile'))->assertOk()->assertSee(route('hero'))->assertSee('Held-level 6');
    }
}

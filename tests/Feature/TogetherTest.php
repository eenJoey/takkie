<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Enums\HouseholdRole;
use App\Livewire\Lists\Show;
use App\Livewire\Shared\PointsBadge;
use App\Models\Household;
use App\Models\ListCategory;
use App\Models\TaskCompletion;
use App\Models\TodoList;
use App\Models\User;
use App\Support\CurrentHousehold;
use App\Support\QuickAdd;
use App\Support\Streak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Samen boodschappen doen (live afvinken), snel toevoegen en streaks. */
class TogetherTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $partner;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['name' => 'Joey']);
        $this->partner = User::factory()->create(['name' => 'Sam']);
        $this->household = app(CreateHousehold::class)->handle($this->me, 'Thuis');
        $this->household->addMember($this->partner, HouseholdRole::Member);
        $this->actingAs($this->me);
    }

    private function category(): ListCategory
    {
        $list = TodoList::factory()->for($this->household)->create(['name' => 'Boodschappen']);

        return $list->categories()->create(['name' => 'Supermarkt', 'color' => 'mint']);
    }

    public function test_quick_add_splits_on_commas_semicolons_and_new_lines(): void
    {
        $this->assertSame(['melk', 'brood', 'kaas', 'eieren'], QuickAdd::split(" melk, brood;kaas\n eieren ,, "));

        $category = $this->category();
        Livewire::test(Show::class, ['list' => $category->list])
            ->set('newItemText', 'melk, brood, kaas')
            ->call('addItem', $category->id);

        $this->assertSame(['melk', 'brood', 'kaas'], $category->tasks()->orderBy('id')->pluck('title')->all());
    }

    public function test_everyone_sees_who_checked_an_item(): void
    {
        $category = $this->category();
        $task = $category->list->tasks()->create(['list_category_id' => $category->id, 'title' => 'Melk']);

        $this->actingAs($this->partner);
        Livewire::test(Show::class, ['list' => $category->list])->call('toggleItem', $task->id);
        $this->assertSame($this->partner->id, $task->fresh()->completed_by);

        // ik zie dat Sam het net pakte
        $this->actingAs($this->me);
        app(CurrentHousehold::class)->forget();
        Livewire::test(Show::class, ['list' => $category->list])
            ->assertSee('Afgevinkt door Sam')
            ->assertSee('net')
            ->assertSeeHtml('wire:poll.3s.visible');

        // weer uitvinken wist wie het was
        Livewire::test(Show::class, ['list' => $category->list])->call('toggleItem', $task->id);
        $this->assertNull($task->fresh()->completed_by);
    }

    public function test_streak_counts_consecutive_days_and_survives_until_tonight(): void
    {
        $this->travelTo(now()->setTimezone(config('app.local_timezone'))->setTime(12, 0));
        $category = $this->category();
        $task = $category->list->tasks()->create(['list_category_id' => $category->id, 'title' => 'Afwas']);
        $complete = fn (int $daysAgo) => TaskCompletion::withoutGlobalScopes()->create([
            'household_id' => $this->household->id,
            'task_id' => $task->id,
            'completed_by' => $this->me->id,
            'completed_at' => now()->subDays($daysAgo),
        ]);

        $this->assertSame(0, Streak::for($this->me, $this->household->id));

        // gisteren en eergisteren: streak 2, ook al is vandaag nog niets gedaan
        $complete(1);
        $complete(2);
        $complete(4);
        $this->assertSame(2, Streak::for($this->me, $this->household->id));

        $complete(0);
        $this->assertSame(3, Streak::for($this->me, $this->household->id));
        $this->assertSame(0, Streak::for($this->partner, $this->household->id));

        Livewire::test(PointsBadge::class)->assertSee('🔥')->assertSee('3 dagen op rij');
    }
}

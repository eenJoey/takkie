<?php

namespace Tests\Feature;

use App\Actions\CompleteTask;
use App\Actions\CreateHousehold;
use App\Enums\ListType;
use App\Enums\RecurrenceType;
use App\Livewire\Lists\Show;
use App\Livewire\Shared\PointsBadge;
use App\Livewire\Tasks\Board;
use App\Livewire\Today;
use App\Models\Household;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\TodoList;
use App\Models\User;
use App\Support\CurrentHousehold;
use App\Support\Today as TodayDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Takenlijst "Huishoudtaken" met punten en herhaling, en de pagina Vandaag (vinkie lovable, M4). */
class TasksTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    private TodoList $tasksList;

    protected function setUp(): void
    {
        parent::setUp();

        // vaste "vandaag": dinsdag 27 oktober 2026, 10:00 in Amsterdam
        Carbon::setTestNow(Carbon::parse('2026-10-27 10:00', 'Europe/Amsterdam'));

        $this->user = User::factory()->create(['name' => 'Joey']);
        $this->household = app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
        $this->tasksList = TodoList::where('type', ListType::Tasks)->sole();
    }

    private function task(array $attributes = []): Task
    {
        return $this->tasksList->tasks()->create([
            'title' => 'Afwassen',
            'points' => 10,
            'recurrence_type' => RecurrenceType::Once,
            'recurrence_interval' => 1,
            'due_date' => '2026-10-27',
            ...$attributes,
        ]);
    }

    private function balance(?User $user = null): int
    {
        return (int) DB::table('household_user')
            ->where('household_id', $this->household->id)
            ->where('user_id', ($user ?? $this->user)->id)
            ->value('points_balance');
    }

    public function test_tasks_list_shows_the_board_grouped_by_recurrence(): void
    {
        $this->task(['title' => 'Stofzuigen', 'recurrence_type' => RecurrenceType::Weekly]);
        $this->task(['title' => 'Planten water', 'recurrence_type' => RecurrenceType::Daily, 'recurrence_interval' => 2]);

        // vinkie-menu-item "Taken" → /taken; het oude lijst-adres stuurt daarheen door
        $this->get(route('lists.index'))->assertSee(route('tasks'))->assertSee('Taken');
        $this->get(route('tasks'))->assertOk()->assertSeeLivewire(Board::class)->assertSee('Stofzuigen');
        $this->get(route('lists.show', $this->tasksList))->assertRedirect(route('tasks'));

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->assertSeeInOrder(['Dagelijks', 'Planten water', 'Elke 2d', 'Wekelijks', 'Stofzuigen', 'Elke 1w']);
    }

    public function test_missing_tasks_list_is_recreated_so_taken_and_vandaag_keep_working(): void
    {
        // zoals in je lokale database na het handmatig koppelen: de lijst "Huishoudtaken" is weg
        $this->tasksList->delete();
        $this->assertSame(0, TodoList::where('type', ListType::Tasks)->count());

        $this->get('/dashboard')->assertOk()->assertSee('Alle taken');
        $this->get(route('tasks'))->assertOk()->assertSee('Nog geen taken');

        $this->assertSame(1, TodoList::where('type', ListType::Tasks)->count());
        $this->assertSame('Huishoudtaken', TodoList::where('type', ListType::Tasks)->sole()->name);
    }

    public function test_create_task_via_the_form(): void
    {
        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('openNew')
            ->assertSet('form.points', 10)
            ->assertSet('form.dueDate', '2026-10-27')
            ->assertDispatched('open-dialog')
            ->set('form.title', 'Badkamer poetsen')
            ->set('form.recurrenceType', 'weekly')
            ->set('form.recurrenceInterval', 2)
            ->set('form.assignedTo', (string) $this->user->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', type: 'success', message: 'Taak aangemaakt');

        $task = Task::where('title', 'Badkamer poetsen')->sole();
        $this->assertSame(RecurrenceType::Weekly, $task->recurrence_type);
        $this->assertSame(2, $task->recurrence_interval);
        $this->assertSame($this->user->id, $task->assigned_to);
        $this->assertSame($this->tasksList->id, $task->list_id);
    }

    public function test_title_is_required_and_assignee_must_be_member(): void
    {
        $stranger = User::factory()->create();

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('openNew')
            ->set('form.title', '')
            ->set('form.assignedTo', (string) $stranger->id)
            ->call('save')
            ->assertHasErrors(['form.title' => 'required', 'form.assignedTo']);
    }

    public function test_edit_task(): void
    {
        $task = $this->task();

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('openEdit', $task->id)
            ->assertSet('form.title', 'Afwassen')
            ->set('form.title', 'Afwassen en afdrogen')
            ->set('form.points', 15)
            ->call('save')
            ->assertDispatched('toast', type: 'success', message: 'Taak bijgewerkt');

        $this->assertSame('Afwassen en afdrogen', $task->fresh()->title);
        $this->assertSame(15, $task->fresh()->points);
    }

    public function test_completing_a_once_task_awards_points_and_hides_it(): void
    {
        $task = $this->task(['points' => 25]);

        Livewire::test(Board::class, ['list' => $this->tasksList])
            ->call('complete', $task->id)
            ->assertDispatched('task-completed')
            ->assertDispatched('points-updated')
            ->assertDispatched('toast', type: 'success', message: '🎉 +25 punten!')
            ->assertDontSee('Afwassen');

        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertNull($task->fresh()->due_date);
        $this->assertSame(25, $this->balance());
        $this->assertSame(25, TaskCompletion::sole()->points_awarded);
    }

    public function test_recurring_task_moves_to_its_next_date_in_the_schedule(): void
    {
        // vandaag is dinsdag 27 oktober
        $daily = $this->task(['recurrence_type' => RecurrenceType::Daily, 'recurrence_interval' => 2, 'due_date' => '2026-10-27']);
        $weekly = $this->task(['recurrence_type' => RecurrenceType::Weekly, 'recurrence_interval' => 1]);
        $monthly = $this->task(['recurrence_type' => RecurrenceType::Monthly, 'recurrence_interval' => 1, 'due_date' => '2026-10-31']);

        $complete = app(CompleteTask::class);
        $complete->handle($this->user, $daily);
        $complete->handle($this->user, $weekly);
        $complete->handle($this->user, $monthly);

        $this->assertSame('2026-10-29', $daily->fresh()->due_date->toDateString());
        $this->assertSame('2026-11-03', $weekly->fresh()->due_date->toDateString());
        // vroeg afgevinkt, en geen overloop: 31 oktober + 1 maand = 30 november
        $this->assertSame('2026-11-30', $monthly->fresh()->due_date->toDateString());
        $this->assertNull($daily->fresh()->completed_at);
        $this->assertSame(30, $this->balance());
    }

    public function test_a_late_task_is_done_once_and_then_follows_its_schedule_again(): void
    {
        // elke 3 dagen vanaf 20 oktober: 20, 23, 26, 29 … — vandaag (27e) te laat afgevinkt
        $everyThree = $this->task(['recurrence_type' => RecurrenceType::Daily, 'recurrence_interval' => 3, 'due_date' => '2026-10-20']);
        // dagelijks, al een week te laat: morgen gewoon weer
        $daily = $this->task(['recurrence_type' => RecurrenceType::Daily, 'recurrence_interval' => 1, 'due_date' => '2026-10-20']);
        // wekelijks op maandag, vorige week gemist
        $weekly = $this->task(['recurrence_type' => RecurrenceType::Weekly, 'recurrence_interval' => 1, 'due_date' => '2026-10-19']);
        // maandelijks op de 31e sinds januari
        $monthly = $this->task(['recurrence_type' => RecurrenceType::Monthly, 'recurrence_interval' => 1, 'due_date' => '2026-01-31']);

        $this->assertTrue($everyThree->isOverdue(TodayDate::date()));

        $complete = app(CompleteTask::class);
        foreach ([$everyThree, $daily, $weekly, $monthly] as $task) {
            $complete->handle($this->user, $task);
        }

        $this->assertSame('2026-10-29', $everyThree->fresh()->due_date->toDateString());
        $this->assertSame('2026-10-28', $daily->fresh()->due_date->toDateString());
        $this->assertSame('2026-11-02', $weekly->fresh()->due_date->toDateString());
        $this->assertSame('2026-10-31', $monthly->fresh()->due_date->toDateString());

        // nu nergens meer te laat, en maar één keer punten per taak
        foreach ([$everyThree, $daily, $weekly, $monthly] as $task) {
            $this->assertFalse($task->fresh()->isOverdue(TodayDate::date()));
        }
        $this->assertSame(40, $this->balance());
    }

    public function test_points_go_to_whoever_completes_the_task(): void
    {
        $sanne = User::factory()->create();
        $this->household->addMember($sanne);
        $task = $this->task(['assigned_to' => $this->user->id, 'points' => 7]);

        app(CompleteTask::class)->handle($sanne, $task);

        $this->assertSame(7, $this->balance($sanne));
        $this->assertSame(0, $this->balance());
    }

    public function test_checklist_items_cannot_earn_points(): void
    {
        $checklist = TodoList::create(['name' => 'Boodschappen']);
        $item = $checklist->tasks()->create(['title' => 'Melk', 'points' => 50]);

        Livewire::test(Board::class, ['list' => $this->tasksList])->call('complete', $item->id)->assertNotFound();

        $this->expectExceptionMessage('Alleen taken uit de takenlijst leveren punten op');
        app(CompleteTask::class)->handle($this->user, $item);
    }

    public function test_cannot_complete_a_task_of_another_household(): void
    {
        $other = User::factory()->create();
        $foreignHousehold = app(CreateHousehold::class)->handle($other, 'Buren');
        $foreignTask = app(CurrentHousehold::class)->runAs($foreignHousehold, fn () => TodoList::where('type', ListType::Tasks)->sole()
            ->tasks()->create(['title' => 'Niet van jou', 'points' => 99, 'due_date' => '2026-10-27']));

        $this->actingAs($this->user);
        app(CurrentHousehold::class)->forget();

        Livewire::test(Today::class)->call('complete', $foreignTask->id)->assertNotFound();
        $this->assertSame(0, $this->balance());
    }

    public function test_today_shows_due_and_overdue_tasks_but_not_later_ones(): void
    {
        $this->task(['title' => 'Vandaag doen']);
        $this->task(['title' => 'Vergeten taak', 'due_date' => '2026-10-24']);
        $this->task(['title' => 'Volgende week', 'due_date' => '2026-11-03']);

        Livewire::test(Today::class)
            ->assertSee('Vandaag')
            ->assertSee('Vandaag doen')
            ->assertSee('Vergeten taak')
            ->assertSee('Te laat — stond op 24 okt')
            ->assertDontSee('Volgende week')
            ->assertSee('Goedemorgen');
    }

    public function test_today_projects_recurring_tasks_on_future_days(): void
    {
        $this->task(['title' => 'Planten water', 'recurrence_type' => RecurrenceType::Daily, 'recurrence_interval' => 2]);
        $this->task(['title' => 'Achterstallig', 'due_date' => '2026-10-25']);

        Livewire::test(Today::class)
            ->call('nextDay')
            ->assertSee('Morgen')
            ->assertDontSee('Planten water')
            ->assertSee('Achterstallig')
            ->call('nextDay')
            ->assertSee('Planten water')
            ->assertSee('Gepland (herhaling)')
            ->assertSee('Terug naar vandaag');
    }

    public function test_cannot_complete_on_a_future_day(): void
    {
        $task = $this->task();

        Livewire::test(Today::class)->call('nextDay')->call('complete', $task->id)->assertNotDispatched('task-completed');

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_today_respects_the_local_date_around_midnight(): void
    {
        // 23:30 UTC is in Amsterdam al de volgende dag
        Carbon::setTestNow(Carbon::parse('2026-10-27 23:30', 'UTC'));
        $this->task(['title' => 'Morgen in UTC', 'due_date' => '2026-10-28']);

        Livewire::test(Today::class)->assertSee('Morgen in UTC');
    }

    public function test_points_badge_shows_balance_of_current_household(): void
    {
        app(CompleteTask::class)->handle($this->user, $this->task(['points' => 12]));

        Livewire::test(PointsBadge::class)->assertSee('12');
        $this->get(route('lists.index'))->assertSeeLivewire(PointsBadge::class);
    }

    public function test_the_fixed_tasks_list_page_still_cannot_be_deleted(): void
    {
        Livewire::test(Show::class, ['list' => $this->tasksList])->assertSee('Taken');
        $this->assertFalse($this->user->can('delete', $this->tasksList));
    }
}

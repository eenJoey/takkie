<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Enums\ListType;
use App\Livewire\Lists\Index;
use App\Livewire\Lists\Show;
use App\Models\Household;
use App\Models\ListCategory;
use App\Models\Task;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListsTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Household $household): User
    {
        $user = User::factory()->create();
        $household->addMember($user, HouseholdRole::Owner);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_without_household_cannot_open_a_list(): void
    {
        $list = TodoList::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('lists.show', $list->id))->assertRedirect('/onboarding');
    }

    public function test_lists_page_is_the_home_screen_and_only_shows_own_household(): void
    {
        $mine = Household::factory()->create();
        $other = Household::factory()->create();
        TodoList::factory()->for($mine)->create(['name' => 'Mijn boodschappen']);
        TodoList::factory()->for($other)->create(['name' => 'Andermans lijst']);

        $this->actingAs($this->memberOf($mine))
            ->get(route('lists.index'))
            ->assertOk()
            ->assertSee('Mijn boodschappen')
            ->assertDontSee('Andermans lijst');
    }

    public function test_tasks_list_is_not_shown_between_the_checklists(): void
    {
        $household = Household::factory()->create();
        TodoList::factory()->for($household)->create(['name' => 'Boodschappen']);
        $tasksList = TodoList::factory()->for($household)->tasks()->create();

        $this->actingAs($this->memberOf($household));

        Livewire::test(Index::class)
            ->assertSee('Boodschappen')
            ->assertDontSee('Huishoudtaken')
            ->set('search', 'huishoud')
            ->assertDontSee('Huishoudtaken');

        // wel bereikbaar via het menu-item "Taken" (/taken)
        $this->get(route('lists.show', $tasksList))->assertRedirect(route('tasks'));
    }

    public function test_list_of_another_household_returns_404(): void
    {
        $mine = Household::factory()->create();
        $foreign = TodoList::factory()->create();

        $this->actingAs($this->memberOf($mine))
            ->get(route('lists.show', $foreign->id))
            ->assertNotFound();
    }

    public function test_creating_a_list_makes_a_checklist_with_a_category_of_the_same_name(): void
    {
        $household = Household::factory()->create();
        $this->actingAs($user = $this->memberOf($household));

        Livewire::test(Index::class)
            ->set('name', 'Weekend')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        $list = TodoList::where('name', 'Weekend')->sole();
        $this->assertSame($household->id, $list->household_id);
        $this->assertSame(ListType::Checklist, $list->type);
        $this->assertSame($user->id, $list->created_by);
        $this->assertSame(['Weekend'], $list->categories()->pluck('name')->all());
    }

    public function test_list_name_is_required(): void
    {
        $this->actingAs($this->memberOf(Household::factory()->create()));

        Livewire::test(Index::class)->set('name', '')->call('create')->assertHasErrors(['name' => 'required']);
    }

    public function test_search_filters_lists_by_name(): void
    {
        $household = Household::factory()->create();
        TodoList::factory()->for($household)->create(['name' => 'Boodschappen']);
        TodoList::factory()->for($household)->create(['name' => 'Vakantie']);

        $this->actingAs($this->memberOf($household));

        Livewire::test(Index::class)
            ->set('search', 'vaka')
            ->assertSee('Vakantie')
            ->assertDontSee('Boodschappen');
    }

    public function test_checklist_can_be_deleted_but_the_tasks_list_cannot(): void
    {
        $household = Household::factory()->create();
        $checklist = TodoList::factory()->for($household)->create();
        $tasksList = TodoList::factory()->for($household)->tasks()->create();

        $this->actingAs($this->memberOf($household));

        Livewire::test(Index::class)->call('delete', $checklist->id);
        $this->assertModelMissing($checklist);

        Livewire::test(Index::class)->call('delete', $tasksList->id)->assertForbidden();
        $this->assertModelExists($tasksList);
    }

    public function test_cannot_delete_a_list_of_another_household(): void
    {
        $foreign = TodoList::factory()->create();
        $this->actingAs($this->memberOf(Household::factory()->create()));

        Livewire::test(Index::class)->call('delete', $foreign->id)->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_checklist_items_can_be_checked_and_unchecked(): void
    {
        $task = Task::factory()->create();
        $list = TodoList::withoutGlobalScopes()->find($task->list_id);
        $this->actingAs($this->memberOf($list->household));

        $component = Livewire::test(Show::class, ['list' => $list]);

        $component->call('toggleItem', $task->id);
        $this->assertNotNull($task->fresh()->completed_at);

        $component->call('toggleItem', $task->id);
        $this->assertNull($task->fresh()->completed_at);
        $this->assertSame(0, $task->fresh()->points);
    }

    public function test_items_and_categories_can_be_added_and_deleted(): void
    {
        $category = ListCategory::factory()->create();
        $list = TodoList::withoutGlobalScopes()->find($category->list_id);
        $this->actingAs($this->memberOf($list->household));

        Livewire::test(Show::class, ['list' => $list])
            ->set('newItemText', 'Kaas')
            ->call('addItem', $category->id)
            ->set('newCategoryName', 'Drogist')
            ->call('addCategory')
            ->assertSee('Kaas')
            ->assertSee('Drogist');

        $task = Task::where('title', 'Kaas')->sole();
        $this->assertSame($list->household_id, $task->household_id);

        Livewire::test(Show::class, ['list' => $list])
            ->call('deleteItem', $task->id)
            ->call('deleteCategory', $category->id);

        $this->assertModelMissing($task);
        $this->assertModelMissing($category);
    }

    public function test_actions_cannot_touch_items_of_another_list(): void
    {
        $household = Household::factory()->create();
        $list = TodoList::factory()->for($household)->create();
        $otherListTask = Task::factory()->for(
            ListCategory::factory()->for(TodoList::factory()->for($household), 'list')->create(['household_id' => $household->id]),
            'category'
        )->create();

        $this->actingAs($this->memberOf($household));

        Livewire::test(Show::class, ['list' => $list])->call('toggleItem', $otherListTask->id)->assertNotFound();
        $this->assertNull($otherListTask->fresh()->completed_at);
    }

    public function test_actions_cannot_touch_items_of_another_household(): void
    {
        $foreignTask = Task::factory()->create();
        $household = Household::factory()->create();
        $list = TodoList::factory()->for($household)->create();

        $this->actingAs($this->memberOf($household));

        Livewire::test(Show::class, ['list' => $list])->call('deleteItem', $foreignTask->id)->assertNotFound();
        $this->assertModelExists($foreignTask);
    }

    public function test_scope_is_fail_closed_without_a_logged_in_user(): void
    {
        TodoList::factory()->count(2)->create();

        $this->assertSame(0, TodoList::count());
        $this->assertSame(2, TodoList::withoutGlobalScopes()->count());
    }

    public function test_remembers_current_household_on_the_user(): void
    {
        $household = Household::factory()->create();
        $user = $this->memberOf($household);
        $this->assertNull($user->current_household_id);

        $this->actingAs($user)->get(route('lists.index'))->assertOk();

        $this->assertSame($household->id, $user->fresh()->current_household_id);
    }
}

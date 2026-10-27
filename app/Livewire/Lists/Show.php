<?php

namespace App\Livewire\Lists;

use App\Enums\CategoryColor;
use App\Models\TodoList;
use App\Support\CurrentHousehold;
use App\Support\QuickAdd;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Lijstdetail. Voor type checklist: port van takkie ChecklistApp
 * (categorieën, items aan/uit vinken, toevoegen, verwijderen).
 * Samen boodschappen doen: de lijst ververst elke 3 s en toont wie wat afvinkte (net door een ander = gemarkeerd).
 * Elke actie werkt via de relaties van deze lijst, dus nooit op id's van een andere lijst of ander huishouden.
 */
class Show extends Component
{
    public TodoList $list;

    public string $newCategoryName = '';

    public string $newItemText = '';

    public ?int $activeCategory = null;

    public bool $isAddingCategory = false;

    /** Zonder lijst (route /taken): de vaste "Huishoudtaken" van het huidige huishouden. */
    public function mount(?TodoList $list = null): void
    {
        $list = $list?->exists ? $list : app(CurrentHousehold::class)->get()->tasksList();

        $this->authorize('view', $list);
        $this->list = $list;

        // de takenlijst heeft een eigen adres en menu-item ("Taken")
        if (! $list->isChecklist() && request()->routeIs('lists.show')) {
            $this->redirectRoute('tasks', navigate: true);
        }
    }

    public function addCategory(): void
    {
        $name = trim($this->newCategoryName);

        if ($name === '') {
            return;
        }

        $this->authorize('update', $this->list);

        $this->list->categories()->create([
            'name' => mb_substr($name, 0, 255),
            'color' => CategoryColor::random(),
        ]);

        $this->reset('newCategoryName', 'isAddingCategory');
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = $this->list->categories()->findOrFail($categoryId);
        $this->authorize('delete', $category);

        $category->delete();
    }

    /** Snel toevoegen: "melk, brood, kaas" geeft drie items. */
    public function addItem(int $categoryId): void
    {
        $titles = QuickAdd::split($this->newItemText);

        if ($titles === []) {
            return;
        }

        $category = $this->list->categories()->findOrFail($categoryId);
        $this->authorize('update', $category);

        foreach ($titles as $title) {
            $this->list->tasks()->create([
                'list_category_id' => $category->id,
                'title' => $title,
                'created_by' => auth()->id(),
            ]);
        }

        $this->reset('newItemText', 'activeCategory');
    }

    /** Checklist: altijd aan/uit te vinken, geen punten (Q14). */
    public function toggleItem(int $taskId): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('update', $task);

        $task->update($task->isCompleted()
            ? ['completed_at' => null, 'completed_by' => null]
            : ['completed_at' => now(), 'completed_by' => auth()->id()]);
    }

    public function deleteItem(int $taskId): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('delete', $task);

        $task->delete();
    }

    public function render(): View
    {
        $categories = $this->list->categories()
            ->with(['tasks' => fn ($query) => $query->with('completer')->oldest()->orderBy('id')])
            ->oldest()
            ->orderBy('id')
            ->get();

        return view('livewire.lists.show', [
            'categories' => $categories,
        ])->title($this->list->name);
    }
}

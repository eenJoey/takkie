<?php

namespace App\Livewire\Lists;

use App\Enums\CategoryColor;
use App\Models\TodoList;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Lijstdetail. Voor type checklist: port van takkie ChecklistApp
 * (categorieën, items aan/uit vinken, toevoegen, verwijderen).
 * Elke actie werkt via de relaties van deze lijst, dus nooit op id's van een andere lijst of ander huishouden.
 */
class Show extends Component
{
    public TodoList $list;

    public string $newCategoryName = '';

    public string $newItemText = '';

    public ?int $activeCategory = null;

    public bool $isAddingCategory = false;

    public function mount(TodoList $list): void
    {
        $this->authorize('view', $list);
        $this->list = $list;
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

    public function addItem(int $categoryId): void
    {
        $text = trim($this->newItemText);

        if ($text === '') {
            return;
        }

        $category = $this->list->categories()->findOrFail($categoryId);
        $this->authorize('update', $category);

        $this->list->tasks()->create([
            'list_category_id' => $category->id,
            'title' => mb_substr($text, 0, 255),
            'created_by' => auth()->id(),
        ]);

        $this->reset('newItemText', 'activeCategory');
    }

    /** Checklist: altijd aan/uit te vinken, geen punten (Q14). */
    public function toggleItem(int $taskId): void
    {
        $task = $this->list->tasks()->findOrFail($taskId);
        $this->authorize('update', $task);

        $task->update(['completed_at' => $task->isCompleted() ? null : now()]);
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
            ->with(['tasks' => fn ($query) => $query->oldest()->orderBy('id')])
            ->oldest()
            ->orderBy('id')
            ->get();

        return view('livewire.lists.show', [
            'categories' => $categories,
        ])->title($this->list->name);
    }
}

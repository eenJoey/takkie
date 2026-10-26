<?php

namespace App\Livewire\Lists;

use App\Enums\CategoryColor;
use App\Enums\ListType;
use App\Models\TodoList;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Lijstenpagina (home). Port van takkie ListsIndex + TodoListController@store/destroy. */
#[Title('Lijstjes')]
class Index extends Component
{
    public string $search = '';

    public string $name = '';

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $list = TodoList::create([
            'name' => $data['name'],
            'type' => ListType::Checklist,
            'created_by' => auth()->id(),
        ]);

        // takkie maakte bij een nieuwe lijst direct een categorie met dezelfde naam (Q17 nog open)
        $list->categories()->create([
            'name' => $data['name'],
            'color' => CategoryColor::random(),
        ]);

        $this->redirectRoute('lists.show', $list, navigate: true);
    }

    public function delete(int $listId): void
    {
        $list = TodoList::findOrFail($listId);
        $this->authorize('delete', $list);

        $list->delete();
    }

    public function render(): View
    {
        $search = trim($this->search);

        $lists = TodoList::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->withCount('categories')
            // de vaste takenlijst bovenaan, daarna nieuwste eerst (zoals takkie)
            ->orderByRaw('type = ? desc', [ListType::Tasks->value])
            ->latest()
            ->get();

        return view('livewire.lists.index', [
            'lists' => $lists,
        ]);
    }
}

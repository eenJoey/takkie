<?php

namespace App\Livewire\Recipes;

use App\Support\NaarTakkie;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/** /recipes/naar-takkie: uitleg voor de bladwijzer en de iPhone-Opdracht "Naar Takkie". */
#[Title('Naar Takkie')]
class SendToTakkie extends Component
{
    public function render(): View
    {
        return view('livewire.recipes.send-to-takkie', [
            'bookmarklet' => NaarTakkie::bookmarklet(),
            'shortcutScript' => NaarTakkie::shortcutScript(),
        ]);
    }
}

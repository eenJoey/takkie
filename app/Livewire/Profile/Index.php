<?php

namespace App\Livewire\Profile;

use App\Enums\StartPage;
use App\Enums\Theme;
use App\Support\CurrentHousehold;
use App\Support\Hero\Hero;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * /profiel (vinkie lovable): profielfoto, weergavenaam, starttabblad, kleurstelling, huishoudens wisselen en uitloggen, plus meldingen (M9).
 * Van de andere huishoudens tonen we alleen de naam: de inhoud pas na het wisselen.
 */
#[Title('Profiel')]
class Index extends Component
{
    use WithFileUploads;

    public string $name = '';

    #[Validate('image|mimes:jpeg,png,webp|max:5120', message: [
        'image' => 'Alleen JPG, PNG of WebP',
        'mimes' => 'Alleen JPG, PNG of WebP',
        'max' => 'Afbeelding mag maximaal 5 MB zijn',
    ])]
    public $photo;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
    }

    #[Computed]
    public function households(): Collection
    {
        return auth()->user()->households()->orderBy('household_user.joined_at')->get();
    }

    public function saveName(): void
    {
        $this->name = trim($this->name);

        if ($this->name === '') {
            $this->dispatch('toast', type: 'error', message: 'Vul een naam in');

            return;
        }

        if (mb_strlen($this->name) > 60) {
            $this->dispatch('toast', type: 'error', message: 'Naam mag maximaal 60 tekens zijn');

            return;
        }

        auth()->user()->update(['name' => $this->name]);
        $this->dispatch('toast', type: 'success', message: 'Naam opgeslagen');
    }

    /** Wordt aangeroepen zodra er een bestand gekozen is (zoals vinkie: kiezen = direct uploaden). */
    public function updatedPhoto(): void
    {
        try {
            $this->validateOnly('photo');
        } catch (ValidationException $e) {
            $this->reset('photo');
            $this->dispatch('toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        $user = auth()->user();
        $old = $user->avatar;
        $extension = match ($this->photo->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = $this->photo->storeAs("avatars/{$user->id}", 'avatar-'.now()->getTimestampMs().'.'.$extension, 'local');
        $user->update(['avatar' => $path]);

        if ($old && ! str_starts_with($old, 'http') && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        $this->reset('photo');
        $this->dispatch('avatar-updated');
        $this->dispatch('toast', type: 'success', message: 'Profielfoto bijgewerkt');
    }

    public function setStartPage(string $page): void
    {
        $startPage = StartPage::tryFrom($page);
        abort_unless($startPage, 422);

        auth()->user()->update(['start_page' => $startPage]);
        $this->dispatch('toast', type: 'success', message: 'Takkie start voortaan op '.$startPage->label());
    }

    /** Alleen voor jezelf: de kleuren wisselen direct, zonder herladen. */
    public function setTheme(string $value): void
    {
        $theme = Theme::tryFrom($value);
        abort_unless($theme, 422);

        auth()->user()->update(['theme' => $theme]);

        $this->js('document.documentElement.dataset.theme = '.json_encode($theme->value).';'
            .'document.querySelector(\'meta[name="theme-color"]\')?.setAttribute("content", '.json_encode($theme->metaColor()).');');
    }

    /** Wisselen = ook het standaard huishouden: de keuze wordt op de gebruiker onthouden. */
    public function switchHousehold(int $householdId): void
    {
        $household = $this->households->firstWhere('id', $householdId);
        abort_unless($household, 404);

        auth()->user()->forceFill(['current_household_id' => $household->id])->save();
        app(CurrentHousehold::class)->forget();

        session()->flash('toast', ['type' => 'success', 'message' => 'Gewisseld naar '.$household->name]);
        $this->redirectRoute('profile');
    }

    public function render(): View
    {
        return view('livewire.profile.index', [
            'user' => auth()->user(),
            'current' => app(CurrentHousehold::class)->get(),
            'heroLevel' => Hero::for(auth()->user(), app(CurrentHousehold::class)->id())->level(),
        ]);
    }
}

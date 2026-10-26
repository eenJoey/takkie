<?php

namespace App\Livewire\Rewards;

use App\Actions\RedeemReward;
use App\Exceptions\RewardException;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Support\CurrentHousehold;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * /rewards (vinkie lovable): beloningen aanmaken, inwisselen en verwijderen,
 * het scorebord en je laatste 10 inwisselingen.
 */
#[Title('Beloningen')]
class Index extends Component
{
    public string $title = '';

    public string $description = '';

    public int $costPoints = 50;

    public string $icon = '🎁';

    #[Computed]
    public function rewards(): Collection
    {
        return Reward::where('archived', false)->orderBy('cost_points')->orderBy('id')->get();
    }

    #[Computed]
    public function balance(): int
    {
        return (int) DB::table('household_user')
            ->where('household_id', app(CurrentHousehold::class)->id())
            ->where('user_id', auth()->id())
            ->value('points_balance');
    }

    /** Scorebord: leden op huidig puntensaldo (zoals vinkie; niet "deze week"). */
    #[Computed]
    public function leaderboard(): Collection
    {
        return app(CurrentHousehold::class)->get()->users()
            ->orderByDesc('household_user.points_balance')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function history(): Collection
    {
        return RewardRedemption::where('redeemed_by', auth()->id())
            ->with('reward')
            ->latest('redeemed_at')
            ->latest('id')
            ->limit(10)
            ->get();
    }

    public function openNew(): void
    {
        $this->reset('title', 'description', 'costPoints', 'icon');
        $this->resetValidation();
        $this->dispatch('open-dialog', name: 'reward-form');
    }

    public function create(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'costPoints' => ['required', 'integer', 'min:0', 'max:100000'],
            'icon' => ['required', Rule::in(Reward::ICONS)],
        ], [], ['title' => 'titel', 'costPoints' => 'punten kosten']);

        Reward::create([
            'title' => trim($data['title']),
            'description' => trim($data['description']) ?: null,
            'cost_points' => $data['costPoints'],
            'icon' => $data['icon'],
            'created_by' => auth()->id(),
        ]);

        unset($this->rewards);
        $this->dispatch('close-dialog');
        $this->dispatch('toast', type: 'success', message: 'Beloning aangemaakt');
    }

    public function redeem(int $rewardId, RedeemReward $redeemReward): void
    {
        $reward = Reward::findOrFail($rewardId);
        $this->authorize('redeem', $reward);

        try {
            $redeemReward->handle(auth()->user(), $reward);
        } catch (RewardException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->balance, $this->leaderboard, $this->history);
        $this->dispatch('points-updated');
        $this->dispatch('toast', type: 'success', message: 'Beloning ingewisseld! 🎁');
    }

    /** Zoals vinkie: direct verwijderen, de inwisselingen van deze beloning gaan mee. */
    public function delete(int $rewardId): void
    {
        $reward = Reward::findOrFail($rewardId);
        $this->authorize('delete', $reward);

        $reward->delete();
        unset($this->rewards, $this->history);
    }

    #[On('points-updated')]
    public function refreshPoints(): void
    {
        unset($this->balance, $this->leaderboard);
    }

    public function render(): View
    {
        return view('livewire.rewards.index');
    }
}

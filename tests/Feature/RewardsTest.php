<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Actions\RedeemReward;
use App\Livewire\Rewards\Index;
use App\Models\Household;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Beloningen, inwisselen en scorebord (vinkie lovable, M5). */
class RewardsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Joey']);
        $this->household = app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
    }

    private function setBalance(User $user, int $points): void
    {
        DB::table('household_user')
            ->where('household_id', $this->household->id)
            ->where('user_id', $user->id)
            ->update(['points_balance' => $points]);
    }

    private function balance(?User $user = null): int
    {
        return (int) DB::table('household_user')
            ->where('household_id', $this->household->id)
            ->where('user_id', ($user ?? $this->user)->id)
            ->value('points_balance');
    }

    private function reward(array $attributes = []): Reward
    {
        return Reward::create(['title' => '1 uur gamen', 'cost_points' => 50, 'icon' => '🎮', ...$attributes]);
    }

    public function test_rewards_page_shows_rewards_sorted_by_cost(): void
    {
        $this->reward(['title' => 'Pizza', 'cost_points' => 200]);
        $this->reward(['title' => 'Ijsje', 'cost_points' => 20]);

        $this->get('/rewards')->assertOk()->assertSeeInOrder(['Beloningen', 'Ijsje', 'Pizza', 'Scorebord']);
    }

    public function test_empty_state(): void
    {
        Livewire::test(Index::class)->assertSee('Nog geen beloningen');
    }

    public function test_create_reward_with_icon(): void
    {
        Livewire::test(Index::class)
            ->call('openNew')
            ->assertSet('costPoints', 50)
            ->assertSet('icon', '🎁')
            ->set('title', 'Film kiezen')
            ->set('costPoints', 30)
            ->call('$set', 'icon', '🎬')
            ->call('create')
            ->assertHasNoErrors()
            ->assertDispatched('toast', type: 'success', message: 'Beloning aangemaakt');

        $reward = Reward::sole();
        $this->assertSame('Film kiezen', $reward->title);
        $this->assertSame(30, $reward->cost_points);
        $this->assertSame('🎬', $reward->icon);
        $this->assertSame($this->household->id, $reward->household_id);
    }

    public function test_only_vinkie_icons_are_allowed(): void
    {
        Livewire::test(Index::class)
            ->set('title', 'Iets')
            ->set('icon', '<script>')
            ->call('create')
            ->assertHasErrors(['icon']);
    }

    public function test_redeem_deducts_points_and_shows_in_history(): void
    {
        $this->setBalance($this->user, 80);
        $reward = $this->reward();

        Livewire::test(Index::class)
            ->assertSee('Inwisselen')
            ->call('redeem', $reward->id)
            ->assertDispatched('points-updated')
            ->assertDispatched('toast', type: 'success', message: 'Beloning ingewisseld! 🎁')
            ->assertSee('Jouw recente inwisselingen')
            ->assertSee('-50');

        $this->assertSame(30, $this->balance());
        $this->assertSame(50, RewardRedemption::sole()->points_spent);
    }

    public function test_too_expensive_reward_cannot_be_redeemed(): void
    {
        $this->setBalance($this->user, 10);
        $reward = $this->reward();

        Livewire::test(Index::class)
            ->assertSee('Te duur')
            ->call('redeem', $reward->id)
            ->assertDispatched('toast', type: 'error', message: 'Onvoldoende punten om deze beloning in te wisselen');

        $this->assertSame(10, $this->balance());
        $this->assertSame(0, RewardRedemption::count());
    }

    public function test_cost_always_comes_from_the_reward(): void
    {
        $this->setBalance($this->user, 100);
        $reward = $this->reward(['cost_points' => 60]);

        app(RedeemReward::class)->handle($this->user, $reward);

        $this->assertSame(40, $this->balance());
    }

    public function test_delete_reward_removes_its_redemptions_like_vinkie(): void
    {
        $this->setBalance($this->user, 100);
        $reward = $this->reward();
        app(RedeemReward::class)->handle($this->user, $reward);

        Livewire::test(Index::class)->call('delete', $reward->id);

        $this->assertModelMissing($reward);
        $this->assertSame(0, RewardRedemption::count());
    }

    public function test_cannot_redeem_or_delete_reward_of_another_household(): void
    {
        $other = User::factory()->create();
        $foreignHousehold = app(CreateHousehold::class)->handle($other, 'Buren');
        $foreign = app(CurrentHousehold::class)->runAs($foreignHousehold, fn () => Reward::create(['title' => 'Niet van jou', 'cost_points' => 0]));

        $this->actingAs($this->user);
        app(CurrentHousehold::class)->forget();

        Livewire::test(Index::class)->assertDontSee('Niet van jou')->call('redeem', $foreign->id)->assertNotFound();
        Livewire::test(Index::class)->call('delete', $foreign->id)->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_scoreboard_ranks_members_by_balance_with_medals(): void
    {
        $sanne = User::factory()->create(['name' => 'Sanne']);
        $tim = User::factory()->create(['name' => 'Tim']);
        $this->household->addMember($sanne);
        $this->household->addMember($tim);
        $this->setBalance($this->user, 20);
        $this->setBalance($sanne, 90);
        $this->setBalance($tim, 5);

        Livewire::test(Index::class)->assertSeeInOrder(['Scorebord', '🥇', 'Sanne', '🥈', 'Joey', '(jij)', '🥉', 'Tim']);
    }

    public function test_history_shows_only_own_last_ten(): void
    {
        $sanne = User::factory()->create(['name' => 'Sanne']);
        $this->household->addMember($sanne);
        $this->setBalance($this->user, 1000);
        $this->setBalance($sanne, 1000);
        $mine = $this->reward(['title' => 'Mijn beloning', 'cost_points' => 1]);
        $hers = $this->reward(['title' => 'Haar beloning', 'cost_points' => 1]);

        for ($i = 0; $i < 12; $i++) {
            app(RedeemReward::class)->handle($this->user, $mine);
        }
        app(RedeemReward::class)->handle($sanne, $hers);

        $history = Livewire::test(Index::class)->instance()->history;
        $this->assertCount(10, $history);
        $this->assertTrue($history->every(fn ($r) => $r->redeemed_by === $this->user->id));
    }

    public function test_navigation_and_today_link_to_rewards(): void
    {
        $this->get('/dashboard')->assertSee(route('rewards'));
    }
}

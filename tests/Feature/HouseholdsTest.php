<?php

namespace Tests\Feature;

use App\Actions\AcceptInvite;
use App\Actions\CreateHousehold;
use App\Enums\HouseholdRole;
use App\Enums\ListType;
use App\Livewire\Household\Index as HouseholdPage;
use App\Livewire\InviteShow;
use App\Livewire\Onboarding;
use App\Livewire\Shared\HouseholdSwitcher;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\Scopes\HouseholdScope;
use App\Models\TodoList;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Huishoudens, onboarding, uitnodigingen en wisselen, zoals vinkie (MIGRATIE-PLAN.md, M3). */
class HouseholdsTest extends TestCase
{
    use RefreshDatabase;

    private function householdWithOwner(string $name = 'Familie Jansen'): array
    {
        $owner = User::factory()->create();
        $household = app(CreateHousehold::class)->handle($owner, $name);

        return [$household, $owner];
    }

    private function inviteFor(Household $household, User $owner, array $attributes = []): HouseholdInvite
    {
        return app(CurrentHousehold::class)->runAs($household, fn () => HouseholdInvite::create(['created_by' => $owner->id, ...$attributes]));
    }

    public function test_user_without_household_is_sent_to_onboarding(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/onboarding');
    }

    public function test_onboarding_redirects_home_when_user_already_has_a_household(): void
    {
        [, $owner] = $this->householdWithOwner();

        $this->actingAs($owner);
        Livewire::test(Onboarding::class)->assertRedirect('/');
    }

    public function test_creating_a_household_makes_owner_and_fixed_tasks_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Onboarding::class)
            ->set('name', 'Familie Jansen')
            ->call('create')
            ->assertRedirect('/');

        $household = Household::where('name', 'Familie Jansen')->sole();
        $this->assertTrue($user->isOwnerOf($household));
        $this->assertSame($household->id, $user->fresh()->current_household_id);

        $lists = TodoList::withoutGlobalScope(HouseholdScope::class)->where('household_id', $household->id)->get();
        $this->assertCount(1, $lists);
        $this->assertSame('Huishoudtaken', $lists->first()->name);
        $this->assertSame(ListType::Tasks, $lists->first()->type);
        $this->assertSame('Huishouden aangemaakt!', session('toast')['message']);
    }

    public function test_joining_with_a_code_or_link_from_onboarding(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $invite = $this->inviteFor($household, $owner);
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Onboarding::class)
            ->set('code', '  '.route('invites.show', $invite->code).'  ')
            ->call('join')
            ->assertRedirect('/');

        $this->assertTrue($user->isMemberOf($household));
        $this->assertFalse($user->isOwnerOf($household));
        $this->assertTrue($invite->fresh()->accepted);
        $this->assertSame($household->id, $user->fresh()->current_household_id);
    }

    public function test_invalid_used_and_expired_codes_are_refused_with_vinkie_messages(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $used = $this->inviteFor($household, $owner, ['accepted' => true]);
        $expired = $this->inviteFor($household, $owner, ['expires_at' => now()->subDay()]);
        $this->actingAs(User::factory()->create());

        $cases = ['kort' => 'Ongeldige uitnodiging', str_repeat('x', 20) => 'Ongeldige uitnodiging',
            $used->code => 'Uitnodiging al gebruikt', $expired->code => 'Uitnodiging is verlopen'];

        foreach ($cases as $code => $message) {
            Livewire::test(Onboarding::class)
                ->set('code', $code)
                ->call('join')
                ->assertNoRedirect()
                ->assertDispatched('toast', type: 'error', message: $message);
        }
    }

    public function test_invite_page_shows_household_name_to_guests(): void
    {
        [$household, $owner] = $this->householdWithOwner('Familie de Vries');
        $invite = $this->inviteFor($household, $owner);

        $this->get(route('invites.show', $invite->code))
            ->assertOk()
            ->assertSee('Je bent uitgenodigd!')
            ->assertSee('Familie de Vries')
            ->assertSee('Eerst inloggen');

        $this->get(route('invites.show', 'bestaat-niet-'.str_repeat('x', 10)))
            ->assertOk()
            ->assertSee('Uitnodiging niet gevonden of verlopen.');
    }

    public function test_logged_in_user_accepts_invite_from_the_invite_page(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $invite = $this->inviteFor($household, $owner);
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(InviteShow::class, ['code' => $invite->code])
            ->call('accept')
            ->assertRedirect('/');

        $this->assertTrue($user->isMemberOf($household));
        $this->assertSame('Je bent toegevoegd!', session('toast')['message']);

        // eenmalig: daarna niet meer zichtbaar
        $this->get(route('invites.show', $invite->code))->assertSee('Uitnodiging niet gevonden of verlopen.');
    }

    public function test_accepting_twice_as_member_does_not_change_role(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $invite = $this->inviteFor($household, $owner);

        app(AcceptInvite::class)->handle($owner, $invite->code);

        $this->assertTrue($owner->isOwnerOf($household));
    }

    public function test_owner_sees_members_and_manages_invites(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $member = User::factory()->create(['name' => 'Sanne']);
        $household->addMember($member);
        $this->actingAs($owner);

        $page = Livewire::test(HouseholdPage::class)
            ->assertSee('2 leden')
            ->assertSee('Sanne')
            ->assertSee('(jij)')
            ->assertSee('Nog geen openstaande uitnodigingen.')
            ->call('createInvite')
            ->assertDispatched('toast', type: 'success', message: 'Uitnodiging aangemaakt');

        $invite = HouseholdInvite::sole();
        $page->assertSee($invite->url());

        $page->set('inviteEmail', 'oma@voorbeeld.nl')->call('inviteByEmail');
        $this->assertSame(2, HouseholdInvite::count());
        $this->assertSame('oma@voorbeeld.nl', HouseholdInvite::latest('id')->first()->email);
    }

    public function test_members_cannot_create_invites_and_do_not_see_them(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $this->inviteFor($household, $owner);
        $member = User::factory()->create();
        $household->addMember($member);
        $this->actingAs($member);

        Livewire::test(HouseholdPage::class)
            ->assertDontSee('Uitnodigingen')
            ->assertSee('Huishouden verlaten')
            ->call('createInvite')
            ->assertForbidden();
    }

    public function test_member_can_leave_but_owner_cannot(): void
    {
        [$household, $owner] = $this->householdWithOwner();
        $member = User::factory()->create();
        $household->addMember($member);

        $this->actingAs($owner);
        Livewire::test(HouseholdPage::class)->call('leave')->assertForbidden();

        $this->actingAs($member);
        app(CurrentHousehold::class)->forget();
        Livewire::test(HouseholdPage::class)->call('leave')->assertRedirect('/');

        $this->assertFalse($member->isMemberOf($household));
        $this->get('/')->assertRedirect('/onboarding');
    }

    public function test_new_household_from_household_page_becomes_current(): void
    {
        [, $owner] = $this->householdWithOwner();
        $this->actingAs($owner);

        Livewire::test(HouseholdPage::class)
            ->set('newHouseholdName', 'Vakantiehuis')
            ->call('createHousehold')
            ->assertRedirect(route('household'));

        $new = Household::where('name', 'Vakantiehuis')->sole();
        $this->assertSame($new->id, $owner->fresh()->current_household_id);
        $this->assertSame(2, $owner->households()->count());
    }

    public function test_switcher_changes_current_household_and_refuses_foreign_ones(): void
    {
        [$first, $owner] = $this->householdWithOwner('Eerste');
        $second = app(CreateHousehold::class)->handle($owner, 'Tweede');
        [$foreign] = $this->householdWithOwner('Andermans');
        $this->actingAs($owner);

        Livewire::test(HouseholdSwitcher::class)
            ->assertSee('Eerste')
            ->assertSee('Tweede')
            ->assertDontSee('Andermans')
            ->call('switchTo', $first->id)
            ->assertRedirect();

        $this->assertSame($first->id, $owner->fresh()->current_household_id);

        Livewire::test(HouseholdSwitcher::class)->call('switchTo', $foreign->id)->assertNotFound();
        $this->assertSame($first->id, $owner->fresh()->current_household_id);
        $this->assertNotSame($second->id, $owner->fresh()->current_household_id);
    }

    public function test_lists_follow_the_current_household(): void
    {
        [$first, $owner] = $this->householdWithOwner('Eerste');
        $second = app(CreateHousehold::class)->handle($owner, 'Tweede');
        TodoList::factory()->for($first)->create(['name' => 'Lijst van eerste']);
        TodoList::factory()->for($second)->create(['name' => 'Lijst van tweede']);

        $owner->forceFill(['current_household_id' => $first->id])->save();
        $this->actingAs($owner)->get(route('lists.index'))->assertSee('Lijst van eerste')->assertDontSee('Lijst van tweede');
    }

    public function test_household_role_is_stored_on_membership(): void
    {
        [$household, $owner] = $this->householdWithOwner();

        $this->assertSame(HouseholdRole::Owner, $household->users()->find($owner->id)->pivot->role);
    }
}

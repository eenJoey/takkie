<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Enums\StartPage;
use App\Enums\Theme;
use App\Livewire\Profile\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Profielpagina (vinkie lovable /profiel, M8). */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->user = User::factory()->create(['name' => 'Joey']);
        app(CreateHousehold::class)->handle($this->user, 'Thuis');
        $this->actingAs($this->user);
    }

    public function test_profile_page_shows_name_email_and_logout(): void
    {
        $this->get('/profiel')->assertOk()
            ->assertSee('Profiel')
            ->assertSee('Weergavenaam')
            ->assertSee($this->user->email)
            ->assertSee('Starttabblad')
            ->assertSee('Huishoudens')
            ->assertSee('Uitloggen');
    }

    public function test_header_links_to_profile_with_name(): void
    {
        $this->get(route('lists.index'))->assertSee(route('profile'))->assertSee('Joey');
    }

    public function test_name_can_be_changed_with_vinkie_validation(): void
    {
        Livewire::test(Index::class)->set('name', '  Joey de Bo  ')->call('saveName')
            ->assertDispatched('toast', type: 'success', message: 'Naam opgeslagen');
        $this->assertSame('Joey de Bo', $this->user->fresh()->name);

        Livewire::test(Index::class)->set('name', '   ')->call('saveName')
            ->assertDispatched('toast', type: 'error', message: 'Vul een naam in');

        Livewire::test(Index::class)->set('name', str_repeat('a', 61))->call('saveName')
            ->assertDispatched('toast', type: 'error', message: 'Naam mag maximaal 60 tekens zijn');
        $this->assertSame('Joey de Bo', $this->user->fresh()->name);
    }

    public function test_avatar_upload_replaces_old_file(): void
    {
        Livewire::test(Index::class)
            ->set('photo', UploadedFile::fake()->image('foto.png', 200, 200))
            ->assertDispatched('toast', type: 'success', message: 'Profielfoto bijgewerkt');

        $first = $this->user->fresh()->avatar;
        $this->assertStringStartsWith("avatars/{$this->user->id}/avatar-", $first);
        $this->assertStringEndsWith('.png', $first);
        Storage::disk('local')->assertExists($first);

        Livewire::test(Index::class)->set('photo', UploadedFile::fake()->image('nieuw.jpg'));

        $second = $this->user->fresh()->avatar;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_avatar_upload_rejects_wrong_type_and_too_large(): void
    {
        Livewire::test(Index::class)
            ->set('photo', UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'))
            ->assertDispatched('toast', type: 'error', message: 'Alleen JPG, PNG of WebP');

        Livewire::test(Index::class)
            ->set('photo', UploadedFile::fake()->image('groot.jpg')->size(6000))
            ->assertDispatched('toast', type: 'error', message: 'Afbeelding mag maximaal 5 MB zijn');

        $this->assertNull($this->user->fresh()->avatar);
    }

    public function test_avatar_is_only_visible_to_self_and_household_members(): void
    {
        Livewire::test(Index::class)->set('photo', UploadedFile::fake()->image('foto.png'));
        $url = route('avatars.show', $this->user);

        $this->get($url)->assertOk();

        $member = User::factory()->create();
        $this->user->households()->first()->addMember($member);
        $this->actingAs($member)->get($url)->assertOk();

        $stranger = User::factory()->create();
        app(CreateHousehold::class)->handle($stranger, 'Buren');
        $this->actingAs($stranger)->get($url)->assertNotFound();
    }

    public function test_google_avatar_url_is_used_directly(): void
    {
        $this->user->update(['avatar' => 'https://lh3.googleusercontent.com/a/voorbeeld']);

        $this->assertSame('https://lh3.googleusercontent.com/a/voorbeeld', $this->user->avatarUrl());
        $this->get(route('lists.index'))->assertSee('https://lh3.googleusercontent.com/a/voorbeeld');
    }

    public function test_households_can_be_switched_from_the_profile(): void
    {
        $second = app(CreateHousehold::class)->handle($this->user, 'Vakantiehuis');
        $first = $this->user->households()->where('name', 'Thuis')->sole();

        Livewire::test(Index::class)->call('switchHousehold', $first->id)->assertRedirect(route('profile'));
        $this->assertSame($first->id, $this->user->fresh()->current_household_id);

        $foreign = app(CreateHousehold::class)->handle(User::factory()->create(), 'Buren');
        $this->actingAs($this->user);
        Livewire::test(Index::class)->call('switchHousehold', $foreign->id)->assertNotFound();
        $this->assertNotSame($second->id, $this->user->fresh()->current_household_id);
    }

    public function test_profile_shows_only_names_of_other_households_until_switching(): void
    {
        $other = User::factory()->create(['name' => 'Huisgenoot']);
        $second = app(CreateHousehold::class)->handle($other, 'Vakantiehuis');
        $second->addMember($this->user, \App\Enums\HouseholdRole::Member);
        app(\App\Support\CurrentHousehold::class)->runAs($second, fn () => \App\Models\TodoList::factory()->for($second)->create(['name' => 'Geheime lijst']));
        $this->user->forceFill(['current_household_id' => $this->user->households()->where('name', 'Thuis')->value('households.id')])->save();

        $this->get('/profiel')
            ->assertSee('Thuis')
            ->assertSee('Vakantiehuis')
            ->assertSee('Wisselen')
            ->assertDontSee('Huisgenoot')
            ->assertDontSee('Geheime lijst');

        Livewire::test(Index::class)->call('switchHousehold', $second->id);
        $this->assertSame($second->id, $this->user->fresh()->current_household_id);
    }

    public function test_start_page_can_be_chosen_and_is_opened_on_start(): void
    {
        $this->get('/')->assertRedirect(route('lists.index'));

        Livewire::test(Index::class)->call('setStartPage', 'dashboard')
            ->assertDispatched('toast', type: 'success', message: 'Takkie start voortaan op Vandaag');
        $this->assertSame(StartPage::Dashboard, $this->user->fresh()->start_page);
        $this->get('/')->assertRedirect(route('dashboard'));

        Livewire::test(Index::class)->call('setStartPage', 'bestaat-niet')->assertStatus(422);
        $this->assertSame(StartPage::Dashboard, $this->user->fresh()->start_page);
    }

    public function test_bottom_nav_on_mobile_has_no_tasks_and_household(): void
    {
        $bottomNav = collect(StartPage::cases())->filter->inBottomNav()->map->label()->values()->all();

        $this->assertSame(['Lijstjes', 'Vandaag', 'Beloningen', 'Recepten'], $bottomNav);
        // in de zijbalk (desktop) staan ze wel
        $this->get(route('lists.index'))->assertSee(route('tasks'))->assertSee(route('household'));
    }

    public function test_own_colors_can_be_chosen_without_names(): void
    {
        $this->get(route('lists.index'))->assertSee('data-theme="takkie-light"', false);
        $this->get('/profiel')
            ->assertSee('role="radiogroup"', false)
            ->assertSee('data-theme="monokai-dark"', false)
            ->assertDontSee('Monokai')
            ->assertDontSee('Oceaan');

        Livewire::test(Index::class)->call('setTheme', 'ocean-dark');
        $this->assertSame(Theme::OceanDark, $this->user->fresh()->theme);
        $this->get(route('lists.index'))
            ->assertSee('data-theme="ocean-dark"', false)
            ->assertSee('content="#0b1e2d"', false);

        // alleen voor jezelf: een huisgenoot houdt de standaard
        $other = User::factory()->create();
        $this->user->households()->first()->addMember($other, \App\Enums\HouseholdRole::Member);
        $this->actingAs($other)->get(route('lists.index'))->assertSee('data-theme="takkie-light"', false);

        $this->actingAs($this->user);
        Livewire::test(Index::class)->call('setTheme', 'bestaat-niet')->assertStatus(422);
        $this->assertSame(Theme::OceanDark, $this->user->fresh()->theme);
    }
}

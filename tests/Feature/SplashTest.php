<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Laadscherm zoals vinkie (🌿), alleen zichtbaar als laden langer dan 0,3 s duurt. */
class SplashTest extends TestCase
{
    use RefreshDatabase;

    public function test_splash_is_on_every_layout_and_starts_hidden_with_a_delay(): void
    {
        $this->get('/login')->assertSee('id="takkie-splash"', false)->assertSee('🌿');
        $this->get('/recipes/discover')->assertSee('id="takkie-splash"', false);

        $user = User::factory()->create();
        app(CreateHousehold::class)->handle($user, 'Thuis');

        $this->actingAs($user)->get(route('lists.index'))
            ->assertSee('id="takkie-splash"', false)
            ->assertSee('takkie-splash-in 0.2s ease-out 0.3s', false)
            ->assertSee('takkie-splash-out 0.3s ease-in 10s', false);
    }
}

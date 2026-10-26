<?php

namespace Database\Seeders;

use App\Actions\CreateHousehold;
use App\Enums\CategoryColor;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Database\Seeder;

/**
 * Alleen voor lokaal/TEST: één testgebruiker met één huishouden en een voorbeeldlijst.
 * Inloggen via /login met het testaccount (wachtwoord: zie UserFactory).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $household = app(CreateHousehold::class)->handle($user, 'Testhuishouden');

        app(CurrentHousehold::class)->set($household);

        $list = $household->lists()->create(['name' => 'Boodschappen', 'created_by' => $user->id]);
        $category = $list->categories()->create(['name' => 'Supermarkt', 'color' => CategoryColor::Mint]);

        foreach (['Melk', 'Brood', 'Appels'] as $title) {
            $list->tasks()->create(['list_category_id' => $category->id, 'title' => $title, 'created_by' => $user->id]);
        }

        app(CurrentHousehold::class)->forget();
    }
}

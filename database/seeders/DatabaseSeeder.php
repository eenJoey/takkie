<?php

namespace Database\Seeders;

use App\Enums\CategoryColor;
use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use App\Support\CurrentHousehold;
use Illuminate\Database\Seeder;

/**
 * Alleen voor lokaal/TEST: één testgebruiker met één huishouden en een voorbeeldlijst.
 * Inloggen lokaal via /login (tijdelijk tot M2).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $household = Household::create(['name' => 'Testhuishouden']);
        $household->addMember($user, HouseholdRole::Owner);
        $user->update(['current_household_id' => $household->id]);

        app(CurrentHousehold::class)->set($household);

        $list = $household->lists()->create(['name' => 'Boodschappen', 'created_by' => $user->id]);
        $category = $list->categories()->create(['name' => 'Supermarkt', 'color' => CategoryColor::Mint]);

        foreach (['Melk', 'Brood', 'Appels'] as $title) {
            $list->tasks()->create(['list_category_id' => $category->id, 'title' => $title, 'created_by' => $user->id]);
        }

        app(CurrentHousehold::class)->forget();
    }
}

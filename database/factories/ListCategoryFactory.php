<?php

namespace Database\Factories;

use App\Enums\CategoryColor;
use App\Models\ListCategory;
use App\Models\TodoList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListCategory>
 */
class ListCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'list_id' => TodoList::factory(),
            'household_id' => fn (array $attributes) => TodoList::withoutGlobalScopes()->find($attributes['list_id'])->household_id,
            'name' => fake()->word(),
            'color' => CategoryColor::random(),
        ];
    }
}

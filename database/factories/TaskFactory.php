<?php

namespace Database\Factories;

use App\Models\ListCategory;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'list_category_id' => ListCategory::factory(),
            'list_id' => fn (array $attributes) => ListCategory::withoutGlobalScopes()->find($attributes['list_category_id'])->list_id,
            'household_id' => fn (array $attributes) => ListCategory::withoutGlobalScopes()->find($attributes['list_category_id'])->household_id,
            'title' => fake()->words(3, true),
        ];
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now()]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ListType;
use App\Models\Household;
use App\Models\TodoList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoList>
 */
class TodoListFactory extends Factory
{
    protected $model = TodoList::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->words(2, true),
            'type' => ListType::Checklist,
        ];
    }

    public function tasks(): static
    {
        return $this->state(['name' => 'Huishoudtaken', 'type' => ListType::Tasks]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(TaskStatus::cases());

        return [
            // Automatically creates or links parents using UUID factories
            'project_id' => Project::factory(),
            'assigned_by' => User::factory(),
            'assigned_to' => $this->faker->boolean(80) ? User::factory() : null, // 80% chance of being assigned

            'name' => $this->faker->realText(50), // Gives a more natural title than a random catchphrase
            'description' => $this->faker->boolean(70) ? $this->faker->paragraph() : null, // Realistic nullable field
            'status' => $status,
            
            'due_date' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('now', '+1 month') : null,
            'completed_at' => $status === TaskStatus::TODO ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * State for a task that is strictly in 'todo' status.
     */
    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::TODO,
            'completed_at' => null,
        ]);
    }

    /**
     * State for a task that is actively being worked on.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    /**
     * State for a task that is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::BLOCKED,
            'completed_at' => null,
        ]);
    }

    /**
     * State for a task that is completed (enforces completion timestamp).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::COMPLETED,
            'completed_at' => $attributes['completed_at'] ?? now(),
        ]);
    }

    /**
     * State for an unassigned task.
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => null,
        ]);
    }
}
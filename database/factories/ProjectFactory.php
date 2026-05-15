<?php

namespace Database\Factories;

use App\Models\Organisation;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Link to an organisation. If one isn't provided, 
            // Laravel will create a new one automatically.
            'organisation_id' => Organisation::factory(),
            'name' => $this->faker->catchPhrase(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * State for a project that is specifically 'active'
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }
}
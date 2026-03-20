<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4),
            'short_description' => fake()->paragraph(),
            'richtext_content' => fake()->paragraphs(3, true),
            'is_published' => true,
            'project_owner_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the project is a concept (not published).
     */
    public function concept(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the project is taken (has a student).
     */
    public function taken(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_name' => fake()->name(),
            'student_email' => fake()->email(),
        ]);
    }

    /**
     * Indicate that the project is available (no student).
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_name' => null,
            'student_email' => null,
        ]);
    }
}

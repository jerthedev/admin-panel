<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JTD\AdminPanel\Tests\Fixtures\Post;
use JTD\AdminPanel\Tests\Fixtures\User;

/**
 * Post Factory
 * 
 * Factory for creating test posts.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_published' => fake()->boolean(70),
            'is_featured' => fake()->boolean(20),
            'user_id' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'is_published' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_published' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}

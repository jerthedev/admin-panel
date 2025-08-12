<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Support;

use Illuminate\Support\Facades\DB;
use JTD\AdminPanel\Tests\Factories\UserFactory;
use JTD\AdminPanel\Tests\Factories\PostFactory;

/**
 * Test Data Seeder
 *
 * Creates comprehensive test data for all field types and testing scenarios.
 */
class TestDataSeeder
{
    /**
     * Seed comprehensive field examples for all 30+ field types.
     */
    public function seedFieldExamples(): array
    {
        $data = [];

        // Text-based fields
        $data['text_fields'] = $this->createTextFieldExamples();

        // Date/Time fields
        $data['datetime_fields'] = $this->createDateTimeFieldExamples();

        // Validation fields
        $data['validation_fields'] = $this->createValidationFieldExamples();

        // Rich content fields
        $data['rich_content_fields'] = $this->createRichContentFieldExamples();

        // Relationship fields
        $data['relationship_fields'] = $this->createRelationshipFieldExamples();

        // Specialized fields
        $data['specialized_fields'] = $this->createSpecializedFieldExamples();

        return $data;
    }

    /**
     * Create specific test scenarios.
     */
    public function createScenario(string $scenario, array $options = []): array
    {
        return match ($scenario) {
            'validation-errors' => $this->createValidationErrorScenario($options),
            'large-dataset' => $this->createLargeDatasetScenario($options),
            'empty-state' => $this->createEmptyStateScenario($options),
            'rich-content' => $this->createRichContentScenario($options),
            'relationships' => $this->createRelationshipScenario($options),
            default => throw new \InvalidArgumentException("Unknown scenario: {$scenario}"),
        };
    }

    /**
     * Create examples for text-based fields.
     */
    protected function createTextFieldExamples(): array
    {
        $users = UserFactory::new()->count(5)->create([
            'name' => fn() => fake()->name(),
            'email' => fn() => fake()->unique()->safeEmail(),
        ]);

        return [
            'count' => $users->count(),
            'examples' => [
                'short_text' => 'Sample Text',
                'long_text' => fake()->paragraph(3),
                'special_chars' => 'Text with "quotes" & symbols!',
                'unicode' => 'Unicode: 🚀 ñáéíóú',
                'empty' => '',
            ],
        ];
    }

    /**
     * Create examples for date/time fields.
     */
    protected function createDateTimeFieldExamples(): array
    {
        $posts = PostFactory::new()->count(5)->create([
            'created_at' => fn() => fake()->dateTimeBetween('-2 years', '-1 year'),
        ]);

        return [
            'count' => $posts->count(),
            'examples' => [
                'past_date' => now()->subDays(30)->toDateString(),
                'future_date' => now()->addDays(30)->toDateString(),
                'current_datetime' => now()->toISOString(),
                'timezone_aware' => now()->setTimezone('America/New_York')->toISOString(),
            ],
        ];
    }

    /**
     * Create examples for validation fields.
     */
    protected function createValidationFieldExamples(): array
    {
        return [
            'examples' => [
                'valid_email' => 'test@example.com',
                'invalid_email' => 'not-an-email',
                'valid_url' => 'https://example.com',
                'invalid_url' => 'not-a-url',
                'strong_password' => 'StrongP@ssw0rd123',
                'weak_password' => '123',
            ],
        ];
    }

    /**
     * Create examples for rich content fields.
     */
    protected function createRichContentFieldExamples(): array
    {
        return [
            'examples' => [
                'markdown' => "# Heading\n\nThis is **bold** and *italic* text.\n\n- List item 1\n- List item 2",
                'html' => '<h1>HTML Content</h1><p>This is <strong>bold</strong> text.</p>',
                'plain_text' => fake()->paragraph(5),
                'empty_content' => '',
            ],
        ];
    }

    /**
     * Create examples for relationship fields.
     */
    protected function createRelationshipFieldExamples(): array
    {
        // Create users and posts with relationships
        $users = UserFactory::new()->count(3)->create();
        $posts = PostFactory::new()->count(10)->create([
            'user_id' => fn() => $users->random()->id,
        ]);

        return [
            'users' => [
                'count' => $users->count(),
                'ids' => $users->pluck('id')->toArray(),
            ],
            'posts' => [
                'count' => $posts->count(),
                'ids' => $posts->pluck('id')->toArray(),
                'with_relationships' => $posts->where('user_id', '!=', null)->count(),
            ],
        ];
    }

    /**
     * Create examples for specialized fields.
     */
    protected function createSpecializedFieldExamples(): array
    {
        return [
            'examples' => [
                'currency' => ['USD' => 1299.99, 'EUR' => 1099.50, 'GBP' => 999.00],
                'numbers' => [0, 42, -15, 3.14159, 1000000],
                'booleans' => [true, false],
                'slugs' => ['hello-world', 'test-slug-123', 'special-chars'],
                'multi_select' => [['option1', 'option2'], ['option3'], []],
            ],
        ];
    }

    /**
     * Create validation error scenario.
     */
    protected function createValidationErrorScenario(array $options): array
    {
        return [
            'scenario' => 'validation-errors',
            'description' => 'Data designed to trigger validation errors',
            'examples' => [
                'missing_required' => null,
                'invalid_email' => 'not-an-email',
                'too_long' => str_repeat('a', 300),
                'negative_number' => -1,
            ],
        ];
    }

    /**
     * Create large dataset scenario.
     */
    protected function createLargeDatasetScenario(array $options): array
    {
        $count = $options['count'] ?? 100;

        $users = UserFactory::new()->count($count)->create();
        $posts = PostFactory::new()->count($count * 2)->create();

        return [
            'scenario' => 'large-dataset',
            'description' => "Large dataset with {$count} users and " . ($count * 2) . " posts",
            'data' => [
                'users' => ['count' => $users->count()],
                'posts' => ['count' => $posts->count()],
            ],
        ];
    }

    /**
     * Create empty state scenario.
     */
    protected function createEmptyStateScenario(array $options): array
    {
        // Ensure tables are empty
        DB::table('users')->truncate();
        DB::table('posts')->truncate();

        return [
            'scenario' => 'empty-state',
            'description' => 'Empty database state for testing empty states',
            'data' => [
                'users' => ['count' => 0],
                'posts' => ['count' => 0],
            ],
        ];
    }

    /**
     * Create rich content scenario.
     */
    protected function createRichContentScenario(array $options): array
    {
        $posts = PostFactory::new()->count(5)->create([
            'content' => fn() => $this->generateRichContent(),
        ]);

        return [
            'scenario' => 'rich-content',
            'description' => 'Posts with rich content for testing editors',
            'data' => [
                'posts' => [
                    'count' => $posts->count(),
                    'ids' => $posts->pluck('id')->toArray(),
                ],
            ],
        ];
    }

    /**
     * Create relationship scenario.
     */
    protected function createRelationshipScenario(array $options): array
    {
        $users = UserFactory::new()->count(5)->create();
        $posts = PostFactory::new()->count(20)->create([
            'user_id' => fn() => $users->random()->id,
        ]);

        return [
            'scenario' => 'relationships',
            'description' => 'Complex relationship data for testing relationship fields',
            'data' => [
                'users' => ['count' => $users->count()],
                'posts' => ['count' => $posts->count()],
                'relationships' => [
                    'posts_per_user' => $posts->groupBy('user_id')->map->count(),
                ],
            ],
        ];
    }

    /**
     * Generate rich content for testing.
     */
    protected function generateRichContent(): string
    {
        return "# Rich Content Example\n\n" .
               "This is **bold** text and *italic* text.\n\n" .
               "## Lists\n\n" .
               "- Item 1\n" .
               "- Item 2\n" .
               "- Item 3\n\n" .
               "### Code Block\n\n" .
               "```php\n" .
               "echo 'Hello World';\n" .
               "```\n\n" .
               "Regular paragraph with [link](https://example.com).";
    }
}

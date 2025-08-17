<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MultiSelect;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MultiSelect Field Integration Test
 *
 * Tests the complete integration between PHP MultiSelect field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Focuses on field configuration and behavior with array storage,
 * testing the Nova API integration.
 */
class MultiSelectFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different multi-select values for testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'skills' => ['php', 'laravel', 'vue'],
            'tags' => ['backend', 'fullstack'],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'skills' => ['javascript', 'react', 'node'],
            'tags' => ['frontend'],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'skills' => ['python', 'django'],
            'tags' => [],
        ]);
    }

    /** @test */
    public function it_creates_multiselect_field_with_nova_api_compatibility(): void
    {
        $field = MultiSelect::make('Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
                'python' => 'Python',
                'java' => 'Java',
            ])
            ->searchable();

        $this->assertEquals('Skills', $field->name);
        $this->assertEquals('skills', $field->attribute);
        $this->assertEquals('MultiSelectField', $field->component);
        $this->assertTrue($field->searchable);
        $this->assertEquals([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
            'java' => 'Java',
        ], $field->options);
    }

    /** @test */
    public function it_resolves_multiselect_values_from_model(): void
    {
        $user = User::find(1);
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'vue' => 'Vue.js',
        ]);

        $field->resolve($user);

        $this->assertEquals(['php', 'laravel', 'vue'], $field->value);
    }

    /** @test */
    public function it_fills_model_with_multiselect_values(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
        ]);

        $user = new User();
        $request = new Request(['skills' => ['php', 'javascript']]);

        $field->fill($request, $user);

        $this->assertEquals(['php', 'javascript'], $user->skills);
    }

    /** @test */
    public function it_validates_selections_against_available_options(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ]);

        $user = new User();
        $request = new Request(['skills' => ['php', 'javascript', 'invalid', 'python']]);

        $field->fill($request, $user);

        // Should only include valid options
        $this->assertEquals(['php', 'javascript'], $user->skills);
    }

    /** @test */
    public function it_handles_empty_selections(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ]);

        $user = new User();
        $request = new Request(['skills' => []]);

        $field->fill($request, $user);

        $this->assertEquals([], $user->skills);
    }

    /** @test */
    public function it_handles_null_selections(): void
    {
        $field = MultiSelect::make('Skills');

        $user = new User();
        $request = new Request(['skills' => null]);

        $field->fill($request, $user);

        $this->assertEquals([], $user->skills);
    }

    /** @test */
    public function it_converts_single_value_to_array(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ]);

        $user = new User();
        $request = new Request(['skills' => 'php']);

        $field->fill($request, $user);

        $this->assertEquals(['php'], $user->skills);
    }

    /** @test */
    public function it_provides_correct_meta_data_for_frontend(): void
    {
        $field = MultiSelect::make('Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
            ])
            ->searchable();

        $meta = $field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertEquals([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ], $meta['options']);
        $this->assertTrue($meta['searchable']);
    }

    /** @test */
    public function it_serializes_correctly_for_json_response(): void
    {
        $field = MultiSelect::make('Programming Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
                'python' => 'Python',
            ])
            ->searchable()
            ->required()
            ->help('Select your programming skills');

        $json = $field->jsonSerialize();

        $this->assertEquals('Programming Skills', $json['name']);
        $this->assertEquals('programming_skills', $json['attribute']);
        $this->assertEquals('MultiSelectField', $json['component']);
        $this->assertEquals([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
        ], $json['options']);
        $this->assertTrue($json['searchable']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your programming skills', $json['helpText']);
    }

    /** @test */
    public function it_handles_enum_options_correctly(): void
    {
        // Create a test enum for this specific test
        if (!enum_exists('TestSkillEnum')) {
            eval('
                enum TestSkillEnum: string {
                    case PHP = "php";
                    case JAVASCRIPT = "javascript";
                    case PYTHON = "python";
                }
            ');
        }

        $field = MultiSelect::make('Skills')->enum('TestSkillEnum');

        $this->assertEquals([
            'php' => 'PHP',
            'javascript' => 'JAVASCRIPT',
            'python' => 'PYTHON',
        ], $field->options);
    }

    /** @test */
    public function it_preserves_selection_order(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'a' => 'Option A',
            'b' => 'Option B',
            'c' => 'Option C',
            'd' => 'Option D',
        ]);

        $user = new User();
        $request = new Request(['skills' => ['d', 'a', 'c']]);

        $field->fill($request, $user);

        $this->assertEquals(['d', 'a', 'c'], $user->skills);
    }

    /** @test */
    public function it_integrates_with_laravel_validation(): void
    {
        $field = MultiSelect::make('Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
            ])
            ->rules('array', 'min:1')
            ->required();

        $this->assertContains('required', $field->rules);
        $this->assertContains('array', $field->rules);
        $this->assertContains('min:1', $field->rules);
    }

    /** @test */
    public function it_handles_nested_attribute_resolution(): void
    {
        $user = User::find(1);
        $user->config = ['preferences' => ['skills' => ['php', 'laravel']]];
        $user->save();

        $field = MultiSelect::make('Skills', 'config.preferences.skills');
        $field->resolve($user);

        $this->assertEquals(['php', 'laravel'], $field->value);
    }

    /** @test */
    public function it_supports_nova_style_chaining(): void
    {
        $field = MultiSelect::make('Skills')
            ->options(['php' => 'PHP'])
            ->searchable()
            ->required()
            ->help('Select skills')
            ->nullable(false);

        $this->assertInstanceOf(MultiSelect::class, $field);
        $this->assertTrue($field->searchable);
        $this->assertContains('required', $field->rules);
        $this->assertEquals('Select skills', $field->helpText);
        $this->assertFalse($field->nullable);
    }
}

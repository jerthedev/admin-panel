<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\MultiSelect;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MultiSelect Field E2E Test
 *
 * Tests the complete end-to-end functionality of MultiSelect fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior with array storage rather than
 * web interface testing (which is handled by Playwright tests).
 */
class MultiSelectFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different multi-select values for E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'skills' => ['php', 'laravel', 'vue', 'javascript'],
            'tags' => ['backend', 'fullstack', 'senior'],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'skills' => ['javascript', 'react', 'node', 'typescript'],
            'tags' => ['frontend', 'junior'],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'skills' => ['python', 'django', 'postgresql'],
            'tags' => ['backend'],
        ]);

        User::factory()->create([
            'id' => 4,
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'skills' => [],
            'tags' => [],
        ]);
    }

    /** @test */
    public function it_creates_multiselect_field_with_complete_nova_api(): void
    {
        $field = MultiSelect::make('Programming Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
                'python' => 'Python',
                'java' => 'Java',
                'csharp' => 'C#',
                'ruby' => 'Ruby',
            ])
            ->searchable()
            ->required()
            ->help('Select your programming skills');

        // Test field configuration
        $this->assertEquals('Programming Skills', $field->name);
        $this->assertEquals('programming_skills', $field->attribute);
        $this->assertEquals('MultiSelectField', $field->component);
        $this->assertTrue($field->searchable);
        $this->assertContains('required', $field->rules);
        $this->assertEquals('Select your programming skills', $field->helpText);

        // Test options configuration
        $expectedOptions = [
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
            'java' => 'Java',
            'csharp' => 'C#',
            'ruby' => 'Ruby',
        ];
        $this->assertEquals($expectedOptions, $field->options);
    }

    /** @test */
    public function it_resolves_multiselect_values_from_database(): void
    {
        $user = User::find(1);
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'vue' => 'Vue.js',
            'javascript' => 'JavaScript',
        ]);

        $field->resolve($user);

        $this->assertEquals(['php', 'laravel', 'vue', 'javascript'], $field->value);
    }

    /** @test */
    public function it_handles_empty_multiselect_values(): void
    {
        $user = User::find(4); // User with empty skills
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ]);

        $field->resolve($user);

        $this->assertEquals([], $field->value);
    }

    /** @test */
    public function it_fills_model_with_multiselect_data(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
        ]);

        $user = new User();
        $request = new \Illuminate\Http\Request(['skills' => ['php', 'javascript']]);

        $field->fill($request, $user);

        $this->assertEquals(['php', 'javascript'], $user->skills);
    }

    /** @test */
    public function it_validates_multiselect_selections_against_options(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'php' => 'PHP',
            'javascript' => 'JavaScript',
        ]);

        $user = new User();
        $request = new \Illuminate\Http\Request(['skills' => ['php', 'javascript', 'invalid', 'python']]);

        $field->fill($request, $user);

        // Should only include valid options
        $this->assertEquals(['php', 'javascript'], $user->skills);
    }

    /** @test */
    public function it_serializes_correctly_for_frontend_consumption(): void
    {
        $field = MultiSelect::make('Development Skills')
            ->options([
                'frontend' => 'Frontend Development',
                'backend' => 'Backend Development',
                'fullstack' => 'Full Stack Development',
                'mobile' => 'Mobile Development',
            ])
            ->searchable()
            ->required()
            ->help('Select your development areas');

        $json = $field->jsonSerialize();

        // Test core field properties
        $this->assertEquals('Development Skills', $json['name']);
        $this->assertEquals('development_skills', $json['attribute']);
        $this->assertEquals('MultiSelectField', $json['component']);
        $this->assertTrue($json['searchable']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your development areas', $json['helpText']);

        // Test options are properly serialized
        $expectedOptions = [
            'frontend' => 'Frontend Development',
            'backend' => 'Backend Development',
            'fullstack' => 'Full Stack Development',
            'mobile' => 'Mobile Development',
        ];
        $this->assertEquals($expectedOptions, $json['options']);
    }

    /** @test */
    public function it_handles_enum_options_in_e2e_scenario(): void
    {
        // Create a test enum for this specific test
        if (!enum_exists('E2ESkillEnum')) {
            eval('
                enum E2ESkillEnum: string {
                    case PHP = "php";
                    case JAVASCRIPT = "javascript";
                    case PYTHON = "python";
                    case JAVA = "java";
                }
            ');
        }

        $field = MultiSelect::make('Skills')->enum('E2ESkillEnum');

        $this->assertEquals([
            'php' => 'PHP',
            'javascript' => 'JAVASCRIPT',
            'python' => 'PYTHON',
            'java' => 'JAVA',
        ], $field->options);

        // Test with actual data
        $user = User::find(1);
        $field->resolve($user);

        // Should resolve existing values that match enum
        $this->assertContains('php', $field->value);
        $this->assertContains('javascript', $field->value);
    }

    /** @test */
    public function it_preserves_selection_order_in_e2e_flow(): void
    {
        $field = MultiSelect::make('Skills')->options([
            'a' => 'Skill A',
            'b' => 'Skill B',
            'c' => 'Skill C',
            'd' => 'Skill D',
        ]);

        $user = new User();
        $request = new \Illuminate\Http\Request(['skills' => ['d', 'a', 'c']]);

        $field->fill($request, $user);

        // Should preserve the order from the request
        $this->assertEquals(['d', 'a', 'c'], $user->skills);

        // Test resolution preserves order
        $field->resolve($user);
        $this->assertEquals(['d', 'a', 'c'], $field->value);
    }

    /** @test */
    public function it_integrates_with_laravel_validation_in_e2e_scenario(): void
    {
        $field = MultiSelect::make('Skills')
            ->options([
                'php' => 'PHP',
                'javascript' => 'JavaScript',
            ])
            ->rules('array', 'min:1')
            ->required();

        // Test validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('array', $field->rules);
        $this->assertContains('min:1', $field->rules);

        // Test field serialization includes validation
        $json = $field->jsonSerialize();
        $this->assertEquals(['array', 'min:1', 'required'], $json['rules']);
    }

    /** @test */
    public function it_handles_complex_real_world_scenario(): void
    {
        // Simulate a complex user profile with multiple skill categories
        $field = MultiSelect::make('Skills', 'skills')
            ->options([
                'php' => 'PHP',
                'laravel' => 'Laravel',
                'vue' => 'Vue.js',
                'javascript' => 'JavaScript',
                'mysql' => 'MySQL',
                'redis' => 'Redis',
                'docker' => 'Docker',
                'aws' => 'AWS',
            ])
            ->searchable()
            ->required()
            ->help('Select all technologies you are proficient in');

        // Test with user who has multiple skills
        $user = User::find(1);
        $field->resolve($user);

        // Should resolve all matching skills
        $resolvedSkills = array_intersect($field->value, array_keys($field->options));
        $this->assertContains('php', $resolvedSkills);
        $this->assertContains('laravel', $resolvedSkills);
        $this->assertContains('vue', $resolvedSkills);
        $this->assertContains('javascript', $resolvedSkills);

        // Test updating skills
        $newSkills = ['php', 'laravel', 'vue', 'mysql', 'docker'];
        $request = new \Illuminate\Http\Request(['skills' => $newSkills]);
        $field->fill($request, $user);

        $this->assertEquals($newSkills, $user->skills);
    }

    /** @test */
    public function it_supports_nova_style_method_chaining(): void
    {
        $field = MultiSelect::make('Skills')
            ->options(['php' => 'PHP', 'js' => 'JavaScript'])
            ->searchable()
            ->required()
            ->help('Select skills')
            ->nullable(false)
            ->sortable(false);

        // Test all methods return the field instance for chaining
        $this->assertInstanceOf(MultiSelect::class, $field);
        $this->assertTrue($field->searchable);
        $this->assertContains('required', $field->rules);
        $this->assertEquals('Select skills', $field->helpText);
        $this->assertFalse($field->nullable);
        $this->assertFalse($field->sortable);
    }
}

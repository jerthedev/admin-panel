<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Select Field Integration Test
 *
 * Tests integration between the PHP Select field class and frontend expectations,
 * ensuring Nova v5 API compatibility and correct JSON/meta for Vue.
 */
class SelectFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_select_field_with_nova_api_compatibility(): void
    {
        $field = Select::make('Status')
            ->options([
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ])
            ->searchable()
            ->displayUsingLabels();

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('SelectField', $field->component);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->displayUsingLabels);
        $this->assertEquals([
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ], $field->options);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $user = User::factory()->create(['color' => 'red']);

        $field = Select::make('Color', 'color')->options([
            'red' => 'Red',
            'blue' => 'Blue',
        ]);

        $field->resolve($user);
        $this->assertEquals('red', $field->value);

        $request = new Request(['color' => 'blue']);
        $field->fill($request, $user);
        $this->assertEquals('blue', $user->color);
    }

    /** @test */
    public function it_accepts_enum_class_via_options(): void
    {
        if (!enum_exists('TestStatusEnum')) {
            eval('enum TestStatusEnum: string { case DRAFT = "draft"; case PUBLISHED = "published"; }');
        }

        $field = Select::make('Status')->options('TestStatusEnum');

        $this->assertEquals([
            'draft' => 'DRAFT',
            'published' => 'PUBLISHED',
        ], $field->options);
    }

    /** @test */
    public function it_serializes_correctly_for_json_response(): void
    {
        $field = Select::make('Publication Status')
            ->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->searchable()
            ->displayUsingLabels()
            ->required()
            ->help('Select the publication status');

        $json = $field->jsonSerialize();

        $this->assertEquals('Publication Status', $json['name']);
        $this->assertEquals('publication_status', $json['attribute']);
        $this->assertEquals('SelectField', $json['component']);
        $this->assertEquals(['draft' => 'Draft', 'published' => 'Published'], $json['options']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['displayUsingLabels']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select the publication status', $json['helpText']);
    }
}


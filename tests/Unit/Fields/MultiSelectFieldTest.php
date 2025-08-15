<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\MultiSelect;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MultiSelect Field Unit Tests
 *
 * Tests for MultiSelect field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MultiSelectFieldTest extends TestCase
{
    public function test_multiselect_field_creation(): void
    {
        $field = MultiSelect::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('MultiSelectField', $field->component);
    }

    public function test_multiselect_field_with_custom_attribute(): void
    {
        $field = MultiSelect::make('User Skills', 'user_skills');

        $this->assertEquals('User Skills', $field->name);
        $this->assertEquals('user_skills', $field->attribute);
    }

    public function test_multiselect_field_default_properties(): void
    {
        $field = MultiSelect::make('Tags');

        $this->assertEquals([], $field->options);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->taggable);
        $this->assertNull($field->maxSelections);
    }

    public function test_multiselect_field_options_configuration(): void
    {
        $options = [
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'vue' => 'Vue.js',
        ];

        $field = MultiSelect::make('Skills')->options($options);

        $this->assertEquals($options, $field->options);
    }

    public function test_multiselect_field_searchable_configuration(): void
    {
        $field = MultiSelect::make('Tags')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_multiselect_field_searchable_false(): void
    {
        $field = MultiSelect::make('Tags')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_multiselect_field_taggable_configuration(): void
    {
        $field = MultiSelect::make('Tags')->taggable();

        $this->assertTrue($field->taggable);
    }

    public function test_multiselect_field_taggable_false(): void
    {
        $field = MultiSelect::make('Tags')->taggable(false);

        $this->assertFalse($field->taggable);
    }

    public function test_multiselect_field_max_selections_configuration(): void
    {
        $field = MultiSelect::make('Tags')->maxSelections(5);

        $this->assertEquals(5, $field->maxSelections);
    }

    public function test_multiselect_field_enum_configuration(): void
    {
        $field = MultiSelect::make('Tags');

        // Test that enum method exists and can be called
        $this->assertTrue(method_exists($field, 'enum'));
    }

    public function test_multiselect_field_fill_ensures_array(): void
    {
        $field = MultiSelect::make('Tags');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => 'single-value']);

        $field->fill($request, $model);

        $this->assertEquals(['single-value'], $model->tags);
    }

    public function test_multiselect_field_fill_preserves_array(): void
    {
        $field = MultiSelect::make('Tags');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => ['tag1', 'tag2', 'tag3']]);

        $field->fill($request, $model);

        $this->assertEquals(['tag1', 'tag2', 'tag3'], $model->tags);
    }

    public function test_multiselect_field_fill_validates_against_options(): void
    {
        $options = [
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'vue' => 'Vue.js',
        ];

        $field = MultiSelect::make('Skills')->options($options);
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['skills' => ['php', 'laravel', 'invalid']]);

        $field->fill($request, $model);

        $this->assertEquals(['php', 'laravel'], $model->skills);
    }

    public function test_multiselect_field_fill_allows_invalid_when_taggable(): void
    {
        $options = [
            'php' => 'PHP',
            'laravel' => 'Laravel',
        ];

        $field = MultiSelect::make('Skills')
            ->options($options)
            ->taggable();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['skills' => ['php', 'laravel', 'custom-tag']]);

        $field->fill($request, $model);

        $this->assertEquals(['php', 'laravel', 'custom-tag'], $model->skills);
    }

    public function test_multiselect_field_fill_enforces_max_selections(): void
    {
        $field = MultiSelect::make('Tags')->maxSelections(2);
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => ['tag1', 'tag2', 'tag3', 'tag4']]);

        $field->fill($request, $model);

        $this->assertEquals(['tag1', 'tag2'], $model->tags);
    }

    public function test_multiselect_field_fill_handles_empty_value(): void
    {
        $field = MultiSelect::make('Tags');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => '']);

        $field->fill($request, $model);

        $this->assertEquals([], $model->tags);
    }

    public function test_multiselect_field_fill_handles_null_value(): void
    {
        $field = MultiSelect::make('Tags');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => null]);

        $field->fill($request, $model);

        $this->assertEquals([], $model->tags);
    }

    public function test_multiselect_field_fill_with_callback(): void
    {
        $field = MultiSelect::make('Tags')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = ['custom', 'tags'];
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['tags' => ['tag1', 'tag2']]);

        $field->fill($request, $model);

        $this->assertEquals(['custom', 'tags'], $model->tags);
    }

    public function test_multiselect_field_meta_includes_all_properties(): void
    {
        $options = ['php' => 'PHP', 'js' => 'JavaScript'];
        $field = MultiSelect::make('Skills')
            ->options($options)
            ->searchable()
            ->taggable()
            ->maxSelections(3);

        $meta = $field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('taggable', $meta);
        $this->assertArrayHasKey('maxSelections', $meta);
        $this->assertEquals($options, $meta['options']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['taggable']);
        $this->assertEquals(3, $meta['maxSelections']);
    }

    public function test_multiselect_field_json_serialization(): void
    {
        $options = ['frontend' => 'Frontend', 'backend' => 'Backend'];
        $field = MultiSelect::make('Development Areas')
            ->options($options)
            ->searchable()
            ->taggable()
            ->maxSelections(2)
            ->required()
            ->help('Select your development areas');

        $json = $field->jsonSerialize();

        $this->assertEquals('Development Areas', $json['name']);
        $this->assertEquals('development_areas', $json['attribute']);
        $this->assertEquals('MultiSelectField', $json['component']);
        $this->assertEquals($options, $json['options']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['taggable']);
        $this->assertEquals(2, $json['maxSelections']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your development areas', $json['helpText']);
    }

    public function test_multiselect_field_complex_scenario(): void
    {
        // Test a complex scenario with all features
        $options = [
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'python' => 'Python',
            'java' => 'Java',
        ];

        $field = MultiSelect::make('Programming Languages')
            ->options($options)
            ->searchable()
            ->taggable()
            ->maxSelections(3);

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([
            'programming_languages' => ['php', 'javascript', 'rust', 'go', 'invalid']
        ]);

        $field->fill($request, $model);

        // Should include valid options + custom tags (rust, go) but limit to maxSelections
        // and filter out invalid options when not taggable... wait, it IS taggable
        // So it should include php, javascript, rust but limit to 3 total
        $this->assertEquals(['php', 'javascript', 'rust'], $model->programming_languages);
    }

    public function test_multiselect_field_enum_with_valid_enum(): void
    {
        // Create a mock enum class for testing
        if (!enum_exists('TestEnum')) {
            eval('
                enum TestEnum: string {
                    case ACTIVE = "active";
                    case INACTIVE = "inactive";
                    case PENDING = "pending";
                }
            ');
        }

        $field = MultiSelect::make('Status');
        $field->enum('TestEnum');

        $expectedOptions = [
            'active' => 'ACTIVE',
            'inactive' => 'INACTIVE',
            'pending' => 'PENDING',
        ];

        $this->assertEquals($expectedOptions, $field->options);
    }

    public function test_multiselect_field_enum_with_invalid_class(): void
    {
        $field = MultiSelect::make('Status');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentEnum is not an enum.');

        $field->enum('NonExistentEnum');
    }

    public function test_multiselect_field_resolve_with_json_string(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => '["tag1", "tag2", "tag3"]'];

        $field->resolve($resource);

        $this->assertEquals(['tag1', 'tag2', 'tag3'], $field->value);
    }

    public function test_multiselect_field_resolve_with_comma_separated_string(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => 'tag1, tag2, tag3'];

        $field->resolve($resource);

        $this->assertEquals(['tag1', 'tag2', 'tag3'], $field->value);
    }

    public function test_multiselect_field_resolve_with_array(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => ['tag1', 'tag2', 'tag3']];

        $field->resolve($resource);

        $this->assertEquals(['tag1', 'tag2', 'tag3'], $field->value);
    }

    public function test_multiselect_field_resolve_with_single_value(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => 'single-tag'];

        $field->resolve($resource);

        $this->assertEquals(['single-tag'], $field->value);
    }

    public function test_multiselect_field_resolve_with_null_value(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => null];

        $field->resolve($resource);

        $this->assertEquals([], $field->value);
    }

    public function test_multiselect_field_resolve_with_invalid_json(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => '{"invalid": json}'];

        $field->resolve($resource);

        // Should fall back to comma-separated parsing and treat as single item
        $this->assertEquals(['{"invalid": json}'], $field->value);
    }

    public function test_multiselect_field_resolve_with_empty_string(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => ''];

        $field->resolve($resource);

        $this->assertEquals([], $field->value);
    }

    public function test_multiselect_field_resolve_filters_empty_values(): void
    {
        $field = MultiSelect::make('Tags');
        $resource = (object) ['tags' => 'tag1, , tag2,  , tag3'];

        $field->resolve($resource);

        // array_filter preserves keys, so we need to account for that
        $expected = ['tag1', 'tag2', 'tag3'];
        $this->assertEquals($expected, array_values($field->value));
    }
}

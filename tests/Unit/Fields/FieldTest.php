<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Base Field Unit Tests
 *
 * Tests for base Field class functionality including validation, visibility,
 * and value handling that applies to all field types.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldTest extends TestCase
{
    public function test_field_rules_configuration(): void
    {
        $field = Text::make('Name')->rules('required', 'max:255');

        $this->assertEquals(['required', 'max:255'], $field->rules);
    }

    public function test_field_creation_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->creationRules('min:3');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['min:3'], $field->creationRules);
    }

    public function test_field_update_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->updateRules('nullable');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['nullable'], $field->updateRules);
    }

    public function test_field_sortable(): void
    {
        $field = Text::make('Name')->sortable();

        $this->assertTrue($field->sortable);
    }

    public function test_field_nullable(): void
    {
        $field = Text::make('Name')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_field_readonly(): void
    {
        $field = Text::make('Name')->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_field_visibility_methods(): void
    {
        $field = Text::make('Name');

        // Default visibility
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Hide on index
        $field->hideFromIndex();
        $this->assertFalse($field->isShownOnIndex());

        // Only on forms
        $field = Text::make('Password')->onlyOnForms();
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Except on forms
        $field = Text::make('ID')->exceptOnForms();
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertFalse($field->isShownOnForms());
    }

    public function test_field_help_text(): void
    {
        $field = Text::make('Name')->help('Enter your full name');

        $this->assertEquals('Enter your full name', $field->helpText);
    }

    public function test_field_placeholder(): void
    {
        $field = Text::make('Name')->placeholder('Enter name here');

        $this->assertEquals('Enter name here', $field->placeholder);
    }

    public function test_field_default_value(): void
    {
        $field = Text::make('Name')->default('John Doe');

        $this->assertEquals('John Doe', $field->default);
    }

    public function test_field_json_serialization(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->sortable()
            ->help('Enter your name');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Name', $json['name']);
        $this->assertEquals('name', $json['attribute']);
        $this->assertEquals('TextField', $json['component']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertTrue($json['sortable']);
        $this->assertEquals('Enter your name', $json['helpText']);
    }

    public function test_field_resolve_value(): void
    {
        $field = Text::make('Name');
        $model = (object) ['name' => 'John Doe'];

        $value = $field->resolveValue($model);

        $this->assertEquals('John Doe', $value);
    }

    public function test_field_resolve_nested_value(): void
    {
        $field = Text::make('User Name', 'user.name');
        $model = (object) [
            'user' => (object) ['name' => 'Jane Doe']
        ];

        $value = $field->resolveValue($model);

        $this->assertEquals('Jane Doe', $value);
    }

    public function test_field_can_be_made_searchable(): void
    {
        $field = Text::make('Name')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_searchable_property_is_included_in_json(): void
    {
        $field = Text::make('Name')->searchable();

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('searchable', $json);
        $this->assertTrue($json['searchable']);
    }

    public function test_searchable_defaults_to_false(): void
    {
        $field = Text::make('Name');

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('searchable', $json);
        $this->assertFalse($json['searchable']);
    }

    public function test_field_can_be_made_required(): void
    {
        $field = Text::make('Name')->required();

        $this->assertContains('required', $field->rules);
    }

    public function test_required_method_adds_to_existing_rules(): void
    {
        $field = Text::make('Name')
            ->rules('max:255')
            ->required();

        $this->assertEquals(['max:255', 'required'], $field->rules);
    }

    public function test_required_method_does_not_duplicate_rule(): void
    {
        $field = Text::make('Name')
            ->rules('required', 'max:255')
            ->required();

        $this->assertEquals(['required', 'max:255'], $field->rules);
    }

    public function test_required_can_be_disabled(): void
    {
        $field = Text::make('Name')
            ->required()
            ->required(false);

        $this->assertNotContains('required', $field->rules);
    }

    public function test_show_on_index_method(): void
    {
        $field = Text::make('Name')->showOnIndex();

        $this->assertTrue($field->showOnIndex);
    }

    public function test_show_on_detail_method(): void
    {
        $field = Text::make('Name')->showOnDetail();

        $this->assertTrue($field->showOnDetail);
    }

    public function test_show_on_creating_method(): void
    {
        $field = Text::make('Name')->showOnCreating();

        $this->assertTrue($field->showOnCreation);
    }

    public function test_show_on_updating_method(): void
    {
        $field = Text::make('Name')->showOnUpdating();

        $this->assertTrue($field->showOnUpdate);
    }

    public function test_display_using_method(): void
    {
        $field = Text::make('Name')->displayUsing(function ($value) {
            return strtoupper($value);
        });

        $model = (object) ['name' => 'john doe'];
        $value = $field->resolveValue($model);

        $this->assertEquals('JOHN DOE', $value);
    }

    public function test_display_using_receives_resource_and_attribute(): void
    {
        $field = Text::make('Name')->displayUsing(function ($value, $resource, $attribute) {
            return $attribute . ': ' . $value;
        });

        $model = (object) ['name' => 'John Doe'];
        $value = $field->resolveValue($model);

        $this->assertEquals('name: John Doe', $value);
    }

    // === AUTHORIZATION METHODS ===

    public function test_field_can_see_callback(): void
    {
        $field = Text::make('Name');
        $callback = function ($request, $resource) {
            return $resource->is_admin ?? false;
        };

        $field->canSee($callback);

        $this->assertEquals($callback, $field->canSeeCallback);
    }

    public function test_field_can_update_callback(): void
    {
        $field = Text::make('Name');
        $callback = function ($request, $resource) {
            return $resource->is_editable ?? true;
        };

        $field->canUpdate($callback);

        $this->assertEquals($callback, $field->canUpdateCallback);
    }

    public function test_field_authorized_to_see_with_callback(): void
    {
        $field = Text::make('Name');
        $field->canSee(function ($request, $resource) {
            return $resource->is_admin ?? false;
        });

        $request = new \Illuminate\Http\Request();
        $resource = (object) ['is_admin' => true];

        $this->assertTrue($field->authorizedToSee($request, $resource));

        $resource->is_admin = false;
        $this->assertFalse($field->authorizedToSee($request, $resource));
    }

    public function test_field_authorized_to_see_default(): void
    {
        $field = Text::make('Name');
        $request = new \Illuminate\Http\Request();

        $this->assertTrue($field->authorizedToSee($request));
    }

    public function test_field_authorized_to_update_with_callback(): void
    {
        $field = Text::make('Name');
        $field->canUpdate(function ($request, $resource) {
            return $resource->is_editable ?? true;
        });

        $request = new \Illuminate\Http\Request();
        $resource = (object) ['is_editable' => false];

        $this->assertFalse($field->authorizedToUpdate($request, $resource));

        $resource->is_editable = true;
        $this->assertTrue($field->authorizedToUpdate($request, $resource));
    }

    public function test_field_authorized_to_update_default(): void
    {
        $field = Text::make('Name');
        $request = new \Illuminate\Http\Request();

        $this->assertTrue($field->authorizedToUpdate($request));
    }

    // === CALLBACK METHODS ===

    public function test_field_resolve_using_callback(): void
    {
        $field = Text::make('Name');
        $callback = function ($resource, $attribute) {
            return strtoupper($resource->{$attribute});
        };

        $field->resolveUsing($callback);

        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_field_fill_using_callback(): void
    {
        $field = Text::make('Name');
        $callback = function ($request, $model, $attribute) {
            $model->{$attribute} = strtolower($request->input($attribute));
        };

        $field->fillUsing($callback);

        $this->assertEquals($callback, $field->fillCallback);
    }

    // === VALUE RESOLUTION METHODS ===

    public function test_field_resolve_value_with_default(): void
    {
        $field = Text::make('Name')->default('Default Name');
        $resource = (object) ['name' => null];

        $resolvedValue = $field->resolveValue($resource);

        $this->assertEquals('Default Name', $resolvedValue);
    }

    public function test_field_resolve_value_without_callbacks(): void
    {
        $field = Text::make('Name');
        $resource = (object) ['name' => 'john doe'];

        $resolvedValue = $field->resolveValue($resource);

        $this->assertEquals('john doe', $resolvedValue);
    }

    // === VISIBILITY METHODS ===

    public function test_field_only_on_forms(): void
    {
        $field = Text::make('Name')->onlyOnForms();

        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_field_except_on_forms(): void
    {
        $field = Text::make('Name')->exceptOnForms();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_field_hide_from_index(): void
    {
        $field = Text::make('Name')->hideFromIndex();

        $this->assertFalse($field->showOnIndex);
    }

    public function test_field_hide_from_detail(): void
    {
        $field = Text::make('Name')->hideFromDetail();

        $this->assertFalse($field->showOnDetail);
    }

    public function test_field_hide_when_creating(): void
    {
        $field = Text::make('Name')->hideWhenCreating();

        $this->assertFalse($field->showOnCreation);
    }

    public function test_field_hide_when_updating(): void
    {
        $field = Text::make('Name')->hideWhenUpdating();

        $this->assertFalse($field->showOnUpdate);
    }

    // === DISPLAY CONFIGURATION METHODS ===

    public function test_field_immutable(): void
    {
        $field = Text::make('Name')->immutable();

        $this->assertTrue($field->immutable);
    }

    public function test_field_immutable_false(): void
    {
        $field = Text::make('Name')->immutable(false);

        $this->assertFalse($field->immutable);
    }

    public function test_field_filterable(): void
    {
        $field = Text::make('Name')->filterable();

        $this->assertTrue($field->filterable);
    }

    public function test_field_filterable_false(): void
    {
        $field = Text::make('Name')->filterable(false);

        $this->assertFalse($field->filterable);
    }

    public function test_field_copyable(): void
    {
        $field = Text::make('Name')->copyable();

        $this->assertTrue($field->copyable);
    }

    public function test_field_copyable_false(): void
    {
        $field = Text::make('Name')->copyable(false);

        $this->assertFalse($field->copyable);
    }

    public function test_field_as_html(): void
    {
        $field = Text::make('Content')->asHtml();

        $this->assertTrue($field->asHtml);
    }

    public function test_field_as_html_false(): void
    {
        $field = Text::make('Content')->asHtml(false);

        $this->assertFalse($field->asHtml);
    }

    public function test_field_text_align(): void
    {
        $field = Text::make('Name')->textAlign('center');

        $this->assertEquals('center', $field->textAlign);
    }

    public function test_field_stacked(): void
    {
        $field = Text::make('Name')->stacked();

        $this->assertTrue($field->stacked);
    }

    public function test_field_stacked_false(): void
    {
        $field = Text::make('Name')->stacked(false);

        $this->assertFalse($field->stacked);
    }

    public function test_field_full_width(): void
    {
        $field = Text::make('Name')->fullWidth();

        $this->assertTrue($field->fullWidth);
    }

    public function test_field_full_width_false(): void
    {
        $field = Text::make('Name')->fullWidth(false);

        $this->assertFalse($field->fullWidth);
    }

    // === META AND UTILITY METHODS ===

    public function test_field_with_meta(): void
    {
        $field = Text::make('Name')->withMeta(['custom' => 'value']);

        $this->assertEquals(['custom' => 'value'], $field->meta);
    }

    public function test_field_with_meta_merge(): void
    {
        $field = Text::make('Name');
        $field->meta = ['existing' => 'data'];
        $field->withMeta(['new' => 'value']);

        $this->assertEquals(['existing' => 'data', 'new' => 'value'], $field->meta);
    }

    public function test_field_meta_method(): void
    {
        $field = Text::make('Name');
        $field->meta = ['test' => 'data'];

        $meta = $field->meta();

        // Text field adds its own meta data, so we check that our custom meta is included
        $this->assertArrayHasKey('test', $meta);
        $this->assertEquals('data', $meta['test']);
    }

    public function test_field_default_value_method(): void
    {
        $field = Text::make('Name')->default('Default Value');

        $this->assertEquals('Default Value', $field->default);
    }

    // === VALIDATION METHODS ===

    public function test_field_required_method(): void
    {
        $field = Text::make('Name')->required();

        $this->assertContains('required', $field->rules);
    }

    public function test_field_required_false(): void
    {
        $field = Text::make('Name')->rules('required', 'max:255')->required(false);

        $this->assertNotContains('required', $field->rules);
        $this->assertContains('max:255', $field->rules);
    }

    public function test_field_required_no_duplicate(): void
    {
        $field = Text::make('Name')->rules('required')->required();

        $requiredCount = count(array_filter($field->rules, fn($rule) => $rule === 'required'));
        $this->assertEquals(1, $requiredCount);
    }

    // === STATUS CHECK METHODS ===

    public function test_field_is_shown_on_index(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->isShownOnIndex());

        $field->hideFromIndex();
        $this->assertFalse($field->isShownOnIndex());
    }

    public function test_field_is_shown_on_detail(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->isShownOnDetail());

        $field->hideFromDetail();
        $this->assertFalse($field->isShownOnDetail());
    }

    public function test_field_is_shown_on_forms(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->isShownOnForms());

        $field->exceptOnForms();
        $this->assertFalse($field->isShownOnForms());

        $field->onlyOnForms();
        $this->assertTrue($field->isShownOnForms());
    }

    public function test_field_show_on_creation_property(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->showOnCreation);

        $field->hideWhenCreating();
        $this->assertFalse($field->showOnCreation);
    }

    public function test_field_show_on_update_property(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->showOnUpdate);

        $field->hideWhenUpdating();
        $this->assertFalse($field->showOnUpdate);
    }

    // === AUTHORIZATION METHOD ===

    public function test_field_authorize_method(): void
    {
        $field = Text::make('Name');
        $request = new \Illuminate\Http\Request();

        // Default authorization should return true
        $this->assertTrue($field->authorize($request));
    }
}

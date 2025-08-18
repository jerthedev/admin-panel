<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Stack;
use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Stack Field Unit Tests
 *
 * Tests for Stack field class with 100% Nova API compatibility.
 * Tests all Nova Stack field features including fields(), line(),
 * addField(), and display functionality with Text, BelongsTo, and Line fields.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class StackFieldTest extends TestCase
{
    /** @test */
    public function it_creates_stack_field_with_nova_syntax(): void
    {
        $field = Stack::make('User Info');

        $this->assertEquals('User Info', $field->name);
        $this->assertEquals('user_info', $field->attribute);
        $this->assertEquals('StackField', $field->component);
    }

    /** @test */
    public function it_creates_stack_field_with_custom_attribute(): void
    {
        $field = Stack::make('Display Name', 'custom_attribute');

        $this->assertEquals('Display Name', $field->name);
        $this->assertEquals('custom_attribute', $field->attribute);
    }

    /** @test */
    public function it_is_readonly_and_nullable_by_default(): void
    {
        $field = Stack::make('User Info');

        $this->assertTrue($field->readonly);
        $this->assertTrue($field->nullable);
    }

    /** @test */
    public function it_sets_fields_array(): void
    {
        $textField = Text::make('Name');
        $lineField = Line::make('Status');
        
        $field = Stack::make('User Info')->fields([
            $textField,
            $lineField,
        ]);

        $fields = $field->getFields();
        $this->assertCount(2, $fields);
        $this->assertSame($textField, $fields[0]);
        $this->assertSame($lineField, $fields[1]);
    }

    /** @test */
    public function it_adds_individual_fields(): void
    {
        $textField = Text::make('Name');
        $lineField = Line::make('Status');
        
        $field = Stack::make('User Info')
            ->addField($textField)
            ->addField($lineField);

        $fields = $field->getFields();
        $this->assertCount(2, $fields);
        $this->assertSame($textField, $fields[0]);
        $this->assertSame($lineField, $fields[1]);
    }

    /** @test */
    public function it_creates_line_fields_with_line_method(): void
    {
        $field = Stack::make('User Info')
            ->line('Status: Active')
            ->line('Last Login: Today');

        $fields = $field->getFields();
        $this->assertCount(2, $fields);
        $this->assertInstanceOf(Line::class, $fields[0]);
        $this->assertInstanceOf(Line::class, $fields[1]);
        $this->assertEquals('Status: Active', $fields[0]->name);
        $this->assertEquals('Last Login: Today', $fields[1]->name);
    }

    /** @test */
    public function it_creates_line_fields_with_callbacks(): void
    {
        $callback = fn($resource) => 'Dynamic Status: ' . $resource->status;
        
        $field = Stack::make('User Info')
            ->line('User Status', $callback);

        $fields = $field->getFields();
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Line::class, $fields[0]);
        $this->assertEquals('User Status', $fields[0]->name);
        $this->assertEquals($callback, $fields[0]->resolveCallback);
    }

    /** @test */
    public function it_combines_different_field_types(): void
    {
        $textField = Text::make('Name');
        $belongsToField = BelongsTo::make('Category');
        
        $field = Stack::make('Product Info')
            ->addField($textField)
            ->line('Status: Available')
            ->addField($belongsToField);

        $fields = $field->getFields();
        $this->assertCount(3, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
        $this->assertInstanceOf(Line::class, $fields[1]);
        $this->assertInstanceOf(BelongsTo::class, $fields[2]);
    }

    /** @test */
    public function it_does_not_fill_model_data(): void
    {
        $field = Stack::make('User Info');
        $request = new Request(['user_info' => 'some value']);
        $model = new \stdClass();

        // Stack fields don't store data, so fill should be a no-op
        $field->fill($request, $model);

        // Model should not have the attribute set
        $this->assertFalse(property_exists($model, 'user_info'));
    }

    /** @test */
    public function it_resolves_all_fields_for_display(): void
    {
        $resource = (object) [
            'name' => 'John Doe',
            'status' => 'Active',
        ];

        $textField = Text::make('Name');
        $lineField = Line::make('Status');
        
        $field = Stack::make('User Info')
            ->addField($textField)
            ->addField($lineField);

        $field->resolveForDisplay($resource);

        // Stack field itself has no value
        $this->assertNull($field->value);

        // But individual fields should be resolved
        $fields = $field->getFields();
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('Active', $fields[1]->value);
    }

    /** @test */
    public function it_resolves_all_fields(): void
    {
        $resource = (object) [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $nameField = Text::make('Name');
        $emailField = Text::make('Email');
        
        $field = Stack::make('User Info')
            ->addField($nameField)
            ->addField($emailField);

        $field->resolve($resource);

        // Stack field itself has no value
        $this->assertNull($field->value);

        // But individual fields should be resolved
        $fields = $field->getFields();
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('john@example.com', $fields[1]->value);
    }

    /** @test */
    public function it_includes_fields_in_meta(): void
    {
        $textField = Text::make('Name');
        $lineField = Line::make('Status')->asSmall();
        
        $field = Stack::make('User Info')
            ->addField($textField)
            ->addField($lineField);

        $meta = $field->meta();

        $this->assertTrue($meta['isStack']);
        $this->assertArrayHasKey('fields', $meta);
        $this->assertCount(2, $meta['fields']);
        
        // Check that fields are properly serialized
        $this->assertEquals('Name', $meta['fields'][0]['name']);
        $this->assertEquals('TextField', $meta['fields'][0]['component']);
        $this->assertEquals('Status', $meta['fields'][1]['name']);
        $this->assertEquals('LineField', $meta['fields'][1]['component']);
        $this->assertTrue($meta['fields'][1]['asSmall']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $textField = Text::make('Name');
        $lineField = Line::make('Status')->asHeading();
        
        $field = Stack::make('User Info')
            ->addField($textField)
            ->addField($lineField)
            ->help('User information stack');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Info', $json['name']);
        $this->assertEquals('user_info', $json['attribute']);
        $this->assertEquals('StackField', $json['component']);
        $this->assertEquals('User information stack', $json['helpText']);
        $this->assertTrue($json['readonly']);
        $this->assertTrue($json['nullable']);
        
        $this->assertArrayHasKey('fields', $json);
        $this->assertCount(2, $json['fields']);
        $this->assertEquals('Name', $json['fields'][0]['name']);
        $this->assertEquals('Status', $json['fields'][1]['name']);
        $this->assertTrue($json['fields'][1]['asHeading']);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_stack_field(): void
    {
        $field = Stack::make('User Info');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Stack::class, $field->fields([]));
        $this->assertInstanceOf(Stack::class, $field->addField(Text::make('Test')));
        $this->assertInstanceOf(Stack::class, $field->line('Test Line'));
        
        // Test component name matches Nova
        $this->assertEquals('StackField', $field->component);
        
        // Test default values match Nova
        $freshField = Stack::make('Fresh');
        $this->assertEquals([], $freshField->getFields());
        $this->assertTrue($freshField->readonly);
        $this->assertTrue($freshField->nullable);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Stack::make('Product Details')
            ->fields([
                Text::make('Name'),
                Line::make('Price', null, fn($r) => '$' . number_format($r->price, 2))->asHeading(),
                Line::make('Status')->asSmall(),
                BelongsTo::make('Category'),
            ])
            ->help('Complete product information')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $this->assertCount(4, $field->getFields());
        $this->assertEquals('Complete product information', $field->helpText);
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_chains_methods_fluently(): void
    {
        $field = Stack::make('User Info')
            ->line('Status: Active')
            ->addField(Text::make('Name'))
            ->line('Last Login: Today')
            ->help('User information display')
            ->showOnIndex();

        $this->assertCount(3, $field->getFields());
        $this->assertEquals('User information display', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    /** @test */
    public function it_handles_empty_fields_array(): void
    {
        $field = Stack::make('Empty Stack');

        $this->assertEquals([], $field->getFields());
        
        $meta = $field->meta();
        $this->assertEquals([], $meta['fields']);
        
        $json = $field->jsonSerialize();
        $this->assertEquals([], $json['fields']);
    }

    /** @test */
    public function it_resolves_fields_with_different_resource_attributes(): void
    {
        $resource = (object) [
            'name' => 'John Doe',
            'profile' => (object) ['status' => 'Premium'],
            'category_id' => 1,
        ];

        $field = Stack::make('User Details')
            ->addField(Text::make('Name'))
            ->addField(Line::make('Status', 'profile.status'))
            ->line('Member Type', fn($r) => $r->profile->status . ' Member');

        $field->resolveForDisplay($resource);

        $fields = $field->getFields();
        $this->assertEquals('John Doe', $fields[0]->value);
        $this->assertEquals('Premium', $fields[1]->value);
        $this->assertEquals('Premium Member', $fields[2]->value);
    }
}

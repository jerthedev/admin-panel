<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphTo;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphTo Field Unit Test.
 *
 * Tests the MorphTo field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class MorphToFieldTest extends TestCase
{
    public function test_morph_to_field_creation(): void
    {
        $field = MorphTo::make('Commentable');

        $this->assertEquals('Commentable', $field->name);
        $this->assertEquals('commentable', $field->attribute);
        $this->assertEquals('MorphToField', $field->component);
        $this->assertEquals('commentable', $field->relationshipName);
    }

    public function test_morph_to_field_with_custom_attribute(): void
    {
        $field = MorphTo::make('Parent Model', 'parent_model');

        $this->assertEquals('Parent Model', $field->name);
        $this->assertEquals('parent_model', $field->attribute);
        $this->assertEquals('parent_model', $field->relationshipName);
    }

    public function test_morph_to_field_default_properties(): void
    {
        $field = MorphTo::make('Commentable');

        $this->assertEquals('commentable', $field->relationshipName);
        $this->assertNull($field->morphType);
        $this->assertNull($field->morphId);
        $this->assertEmpty($field->types);
        $this->assertFalse($field->nullable);
        $this->assertTrue($field->peekable);
        $this->assertNull($field->defaultValue);
        $this->assertNull($field->defaultResourceClass);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertNull($field->modalSize);
        $this->assertNull($field->relatableQueryCallback);
    }

    public function test_morph_to_field_types_configuration(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource'];
        $field = MorphTo::make('Commentable')->types($types);

        $this->assertEquals($types, $field->types);
    }

    public function test_morph_to_field_relationship_configuration(): void
    {
        $field = MorphTo::make('Commentable')->relationship('parentModel');

        $this->assertEquals('parentModel', $field->relationshipName);
    }

    public function test_morph_to_field_morph_type_configuration(): void
    {
        $field = MorphTo::make('Commentable')->morphType('commentable_type');

        $this->assertEquals('commentable_type', $field->morphType);
    }

    public function test_morph_to_field_morph_id_configuration(): void
    {
        $field = MorphTo::make('Commentable')->morphId('commentable_id');

        $this->assertEquals('commentable_id', $field->morphId);
    }

    public function test_morph_to_field_nullable_configuration(): void
    {
        $field = MorphTo::make('Commentable')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_morph_to_field_nullable_false(): void
    {
        $field = MorphTo::make('Commentable')->nullable(false);

        $this->assertFalse($field->nullable);
    }

    public function test_morph_to_field_no_peeking(): void
    {
        $field = MorphTo::make('Commentable')->noPeeking();

        $this->assertFalse($field->peekable);
    }

    public function test_morph_to_field_peekable_configuration(): void
    {
        $field = MorphTo::make('Commentable')->peekable(false);

        $this->assertFalse($field->peekable);
    }

    public function test_morph_to_field_peekable_with_callback(): void
    {
        $field = MorphTo::make('Commentable')->peekable(function () {
            return false;
        });

        $this->assertFalse($field->peekable);
    }

    public function test_morph_to_field_default_value(): void
    {
        $field = MorphTo::make('Commentable')->default(123);

        $this->assertEquals(123, $field->defaultValue);
    }

    public function test_morph_to_field_default_resource(): void
    {
        $field = MorphTo::make('Commentable')->defaultResource('App\\Resources\\PostResource');

        $this->assertEquals('App\\Resources\\PostResource', $field->defaultResourceClass);
    }

    public function test_morph_to_field_searchable_configuration(): void
    {
        $field = MorphTo::make('Commentable')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_morph_to_field_searchable_with_callback(): void
    {
        $field = MorphTo::make('Commentable')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);
    }

    public function test_morph_to_field_searchable_false(): void
    {
        $field = MorphTo::make('Commentable')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_morph_to_field_with_subtitles(): void
    {
        $field = MorphTo::make('Commentable')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_morph_to_field_show_create_relation_button(): void
    {
        $field = MorphTo::make('Commentable')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_to_field_show_create_relation_button_with_callback(): void
    {
        $field = MorphTo::make('Commentable')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_to_field_hide_create_relation_button(): void
    {
        $field = MorphTo::make('Commentable')
            ->showCreateRelationButton()
            ->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_morph_to_field_modal_size(): void
    {
        $field = MorphTo::make('Commentable')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_morph_to_field_relatable_query_using(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = MorphTo::make('Commentable')->relatableQueryUsing($queryCallback);

        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }

    public function test_morph_to_field_fill_with_custom_callback(): void
    {
        $request = new Request(['commentable' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = MorphTo::make('Commentable');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('commentable', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_morph_to_field_meta_includes_all_properties(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource'];

        $field = MorphTo::make('Commentable')
            ->types($types)
            ->relationship('parentModel')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->noPeeking()
            ->default(123)
            ->defaultResource('App\\Resources\\PostResource')
            ->searchable()
            ->withSubtitles()
            ->showCreateRelationButton()
            ->modalSize('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('types', $meta);
        $this->assertArrayHasKey('nullable', $meta);
        $this->assertArrayHasKey('peekable', $meta);
        $this->assertArrayHasKey('defaultValue', $meta);
        $this->assertArrayHasKey('defaultResourceClass', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);

        $this->assertEquals('parentModel', $meta['relationshipName']);
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
        $this->assertEquals($types, $meta['types']);
        $this->assertTrue($meta['nullable']);
        $this->assertFalse($meta['peekable']);
        $this->assertEquals(123, $meta['defaultValue']);
        $this->assertEquals('App\\Resources\\PostResource', $meta['defaultResourceClass']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
    }

    public function test_morph_to_field_json_serialization(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource'];

        $field = MorphTo::make('Parent Model')
            ->types($types)
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->searchable()
            ->showCreateRelationButton()
            ->help('Select parent model');

        $json = $field->jsonSerialize();

        $this->assertEquals('Parent Model', $json['name']);
        $this->assertEquals('parent_model', $json['attribute']);
        $this->assertEquals('MorphToField', $json['component']);
        $this->assertEquals($types, $json['types']);
        $this->assertEquals('commentable_type', $json['morphType']);
        $this->assertEquals('commentable_id', $json['morphId']);
        $this->assertTrue($json['nullable']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Select parent model', $json['helpText']);
    }

    public function test_morph_to_field_complex_configuration(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource', 'App\\Resources\\ProductResource'];

        $field = MorphTo::make('Parent Entity')
            ->types($types)
            ->relationship('parentEntity')
            ->morphType('entity_type')
            ->morphId('entity_id')
            ->nullable()
            ->peekable(false)
            ->default(456)
            ->defaultResource('App\\Resources\\PostResource')
            ->searchable()
            ->withSubtitles()
            ->showCreateRelationButton()
            ->modalSize('xl');

        // Test all configurations are set
        $this->assertEquals($types, $field->types);
        $this->assertEquals('parentEntity', $field->relationshipName);
        $this->assertEquals('entity_type', $field->morphType);
        $this->assertEquals('entity_id', $field->morphId);
        $this->assertTrue($field->nullable);
        $this->assertFalse($field->peekable);
        $this->assertEquals(456, $field->defaultValue);
        $this->assertEquals('App\\Resources\\PostResource', $field->defaultResourceClass);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('xl', $field->modalSize);
    }

    public function test_morph_to_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = MorphTo::make('Commentable', 'commentable', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_morph_to_field_supports_field_chaining(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource'];

        $field = MorphTo::make('Commentable')
            ->types($types)
            ->relationship('parentModel')
            ->morphType('commentable_type')
            ->morphId('commentable_id')
            ->nullable()
            ->noPeeking()
            ->default(789)
            ->defaultResource('App\\Resources\\PostResource')
            ->searchable()
            ->withSubtitles()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->help('Polymorphic parent selection')
            ->showOnIndex();

        $this->assertEquals($types, $field->types);
        $this->assertEquals('parentModel', $field->relationshipName);
        $this->assertEquals('commentable_type', $field->morphType);
        $this->assertEquals('commentable_id', $field->morphId);
        $this->assertTrue($field->nullable);
        $this->assertFalse($field->peekable);
        $this->assertEquals(789, $field->defaultValue);
        $this->assertEquals('App\\Resources\\PostResource', $field->defaultResourceClass);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertEquals('Polymorphic parent selection', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    public function test_morph_to_field_component_name(): void
    {
        $field = MorphTo::make('Commentable');

        $this->assertEquals('MorphToField', $field->component);
    }

    public function test_morph_to_field_attribute_guessing(): void
    {
        // Test various field names and their attribute guessing
        $testCases = [
            ['Parent Model', 'parent_model'],
            ['Commentable Entity', 'commentable_entity'],
            ['Related Item', 'related_item'],
            ['Commentable', 'commentable'],
        ];

        foreach ($testCases as [$name, $expectedAttribute]) {
            $field = MorphTo::make($name);
            $this->assertEquals($expectedAttribute, $field->attribute);
            $this->assertEquals($expectedAttribute, $field->relationshipName);
        }
    }

    public function test_morph_to_field_polymorphic_properties(): void
    {
        $field = MorphTo::make('Commentable')
            ->morphType('commentable_type')
            ->morphId('commentable_id');

        // Test that polymorphic properties are set correctly
        $this->assertEquals('commentable_type', $field->morphType);
        $this->assertEquals('commentable_id', $field->morphId);

        // Test that these are included in meta
        $meta = $field->meta();
        $this->assertEquals('commentable_type', $meta['morphType']);
        $this->assertEquals('commentable_id', $meta['morphId']);
    }

    public function test_morph_to_field_make_method_signature(): void
    {
        // Test basic make method
        $field1 = MorphTo::make('Commentable');
        $this->assertEquals('Commentable', $field1->name);
        $this->assertEquals('commentable', $field1->attribute);

        // Test make method with attribute
        $field2 = MorphTo::make('Commentable', 'parent_model');
        $this->assertEquals('Commentable', $field2->name);
        $this->assertEquals('parent_model', $field2->attribute);

        // Test make method with callback
        $callback = function ($value) {
            return $value;
        };
        $field3 = MorphTo::make('Commentable', 'commentable', $callback);
        $this->assertEquals('Commentable', $field3->name);
        $this->assertEquals('commentable', $field3->attribute);
        $this->assertEquals($callback, $field3->resolveCallback);
    }

    public function test_morph_to_field_types_array_handling(): void
    {
        $types = ['App\\Resources\\PostResource', 'App\\Resources\\VideoResource'];
        $field = MorphTo::make('Commentable')->types($types);

        $this->assertEquals($types, $field->types);
        $this->assertIsArray($field->types);
        $this->assertCount(2, $field->types);
    }

    public function test_morph_to_field_empty_types_array(): void
    {
        $field = MorphTo::make('Commentable')->types([]);

        $this->assertEquals([], $field->types);
        $this->assertIsArray($field->types);
        $this->assertEmpty($field->types);
    }
}

<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\MorphToMany;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MorphToMany Field Unit Test.
 *
 * Tests the MorphToMany field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class MorphToManyFieldTest extends TestCase
{
    public function test_morph_to_many_field_creation(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('MorphToManyField', $field->component);
        $this->assertEquals('tags', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Tags', $field->resourceClass);
    }

    public function test_morph_to_many_field_with_custom_attribute(): void
    {
        $field = MorphToMany::make('Post Tags', 'post_tags');

        $this->assertEquals('Post Tags', $field->name);
        $this->assertEquals('post_tags', $field->attribute);
        $this->assertEquals('post_tags', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\PostTags', $field->resourceClass);
    }

    public function test_morph_to_many_field_default_properties(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertEquals('tags', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Tags', $field->resourceClass);
        $this->assertNull($field->morphType);
        $this->assertNull($field->morphId);
        $this->assertNull($field->localKey);
        $this->assertNull($field->pivotTable);
        $this->assertEmpty($field->pivotFields);
        $this->assertEmpty($field->pivotComputedFields);
        $this->assertEmpty($field->pivotActions);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->collapsable);
        $this->assertFalse($field->collapsedByDefault);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertNull($field->modalSize);
        $this->assertEquals(15, $field->perPage);
        $this->assertNull($field->relatableQueryCallback);
        $this->assertFalse($field->allowDuplicateRelations);
        $this->assertTrue($field->reorderAttachables);
    }

    public function test_morph_to_many_field_resource_configuration(): void
    {
        $field = MorphToMany::make('Tags')->resource('App\\Models\\TagResource');

        $this->assertEquals('App\\Models\\TagResource', $field->resourceClass);
    }

    public function test_morph_to_many_field_relationship_configuration(): void
    {
        $field = MorphToMany::make('Tags')->relationship('postTags');

        $this->assertEquals('postTags', $field->relationshipName);
    }

    public function test_morph_to_many_field_morph_type_configuration(): void
    {
        $field = MorphToMany::make('Tags')->morphType('taggable_type');

        $this->assertEquals('taggable_type', $field->morphType);
    }

    public function test_morph_to_many_field_morph_id_configuration(): void
    {
        $field = MorphToMany::make('Tags')->morphId('taggable_id');

        $this->assertEquals('taggable_id', $field->morphId);
    }

    public function test_morph_to_many_field_local_key_configuration(): void
    {
        $field = MorphToMany::make('Tags')->localKey('id');

        $this->assertEquals('id', $field->localKey);
    }

    public function test_morph_to_many_field_pivot_table_configuration(): void
    {
        $field = MorphToMany::make('Tags')->pivotTable('taggables');

        $this->assertEquals('taggables', $field->pivotTable);
    }

    public function test_morph_to_many_field_fields_configuration(): void
    {
        $fieldsCallback = function () {
            return ['notes'];
        };

        $field = MorphToMany::make('Tags')->fields($fieldsCallback);

        $this->assertEquals($fieldsCallback, $field->pivotFields);
    }

    public function test_morph_to_many_field_actions_configuration(): void
    {
        $actionsCallback = function () {
            return ['activate'];
        };

        $field = MorphToMany::make('Tags')->actions($actionsCallback);

        $this->assertEquals($actionsCallback, $field->pivotActions);
    }

    public function test_morph_to_many_field_searchable_configuration(): void
    {
        $field = MorphToMany::make('Tags')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_morph_to_many_field_searchable_with_callback(): void
    {
        $field = MorphToMany::make('Tags')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);
    }

    public function test_morph_to_many_field_searchable_false(): void
    {
        $field = MorphToMany::make('Tags')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_morph_to_many_field_with_subtitles(): void
    {
        $field = MorphToMany::make('Tags')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_morph_to_many_field_collapsable(): void
    {
        $field = MorphToMany::make('Tags')->collapsable();

        $this->assertTrue($field->collapsable);
    }

    public function test_morph_to_many_field_collapsed_by_default(): void
    {
        $field = MorphToMany::make('Tags')->collapsedByDefault();

        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->collapsable); // Should also be collapsable
    }

    public function test_morph_to_many_field_show_create_relation_button(): void
    {
        $field = MorphToMany::make('Tags')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_to_many_field_show_create_relation_button_with_callback(): void
    {
        $field = MorphToMany::make('Tags')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_morph_to_many_field_hide_create_relation_button(): void
    {
        $field = MorphToMany::make('Tags')
            ->showCreateRelationButton()
            ->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_morph_to_many_field_modal_size(): void
    {
        $field = MorphToMany::make('Tags')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_morph_to_many_field_relatable_query_using(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = MorphToMany::make('Tags')->relatableQueryUsing($queryCallback);

        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }

    public function test_morph_to_many_field_allow_duplicate_relations(): void
    {
        $field = MorphToMany::make('Tags')->allowDuplicateRelations();

        $this->assertTrue($field->allowDuplicateRelations);
    }

    public function test_morph_to_many_field_allow_duplicate_relations_false(): void
    {
        $field = MorphToMany::make('Tags')->allowDuplicateRelations(false);

        $this->assertFalse($field->allowDuplicateRelations);
    }

    public function test_morph_to_many_field_dont_reorder_attachables(): void
    {
        $field = MorphToMany::make('Tags')->dontReorderAttachables();

        $this->assertFalse($field->reorderAttachables);
    }

    public function test_morph_to_many_field_is_only_shown_on_detail_by_default(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_morph_to_many_field_can_be_configured_for_different_views(): void
    {
        $field = MorphToMany::make('Tags')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_morph_to_many_field_fill_with_custom_callback(): void
    {
        $request = new Request(['tags' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = MorphToMany::make('Tags');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('tags', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_morph_to_many_field_meta_includes_all_properties(): void
    {
        $fieldsCallback = function () {
            return ['notes'];
        };
        $actionsCallback = function () {
            return ['activate'];
        };

        $field = MorphToMany::make('Tags')
            ->resource('App\\Models\\TagResource')
            ->relationship('postTags')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->localKey('id')
            ->pivotTable('taggables')
            ->fields($fieldsCallback)
            ->actions($actionsCallback)
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->allowDuplicateRelations()
            ->dontReorderAttachables();

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('morphType', $meta);
        $this->assertArrayHasKey('morphId', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('pivotTable', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('perPage', $meta);
        $this->assertArrayHasKey('allowDuplicateRelations', $meta);
        $this->assertArrayHasKey('reorderAttachables', $meta);

        $this->assertEquals('App\\Models\\TagResource', $meta['resourceClass']);
        $this->assertEquals('postTags', $meta['relationshipName']);
        $this->assertEquals('taggable_type', $meta['morphType']);
        $this->assertEquals('taggable_id', $meta['morphId']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('taggables', $meta['pivotTable']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertEquals(15, $meta['perPage']);
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertFalse($meta['reorderAttachables']);
    }

    public function test_morph_to_many_field_json_serialization(): void
    {
        $field = MorphToMany::make('Post Tags')
            ->resource('App\\Resources\\TagResource')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->help('Manage post tags');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Tags', $json['name']);
        $this->assertEquals('post_tags', $json['attribute']);
        $this->assertEquals('MorphToManyField', $json['component']);
        $this->assertEquals('App\\Resources\\TagResource', $json['resourceClass']);
        $this->assertEquals('taggable_type', $json['morphType']);
        $this->assertEquals('taggable_id', $json['morphId']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals('Manage post tags', $json['helpText']);
    }

    public function test_morph_to_many_field_complex_configuration(): void
    {
        $fieldsCallback = function () {
            return ['notes', 'priority'];
        };
        $actionsCallback = function () {
            return ['activate', 'deactivate'];
        };

        $field = MorphToMany::make('User Tags')
            ->resource('App\\Resources\\TagResource')
            ->relationship('userTags')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->localKey('id')
            ->pivotTable('taggables')
            ->fields($fieldsCallback)
            ->actions($actionsCallback)
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('xl')
            ->allowDuplicateRelations()
            ->dontReorderAttachables();

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\TagResource', $field->resourceClass);
        $this->assertEquals('userTags', $field->relationshipName);
        $this->assertEquals('taggable_type', $field->morphType);
        $this->assertEquals('taggable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('taggables', $field->pivotTable);
        $this->assertEquals($fieldsCallback, $field->pivotFields);
        $this->assertEquals($actionsCallback, $field->pivotActions);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('xl', $field->modalSize);
        $this->assertTrue($field->allowDuplicateRelations);
        $this->assertFalse($field->reorderAttachables);
    }

    public function test_morph_to_many_field_guesses_resource_class_correctly(): void
    {
        $field = MorphToMany::make('User Tags', 'user_tags');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserTags', $field->resourceClass);
    }

    public function test_morph_to_many_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = MorphToMany::make('Tags', 'tags', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_morph_to_many_field_supports_field_chaining(): void
    {
        $fieldsCallback = function () {
            return ['notes'];
        };

        $field = MorphToMany::make('Tags')
            ->resource('App\\Nova\\TagResource')
            ->relationship('postTags')
            ->morphType('taggable_type')
            ->morphId('taggable_id')
            ->localKey('id')
            ->pivotTable('taggables')
            ->fields($fieldsCallback)
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->allowDuplicateRelations()
            ->dontReorderAttachables()
            ->help('Post tags management')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\TagResource', $field->resourceClass);
        $this->assertEquals('postTags', $field->relationshipName);
        $this->assertEquals('taggable_type', $field->morphType);
        $this->assertEquals('taggable_id', $field->morphId);
        $this->assertEquals('id', $field->localKey);
        $this->assertEquals('taggables', $field->pivotTable);
        $this->assertEquals($fieldsCallback, $field->pivotFields);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertTrue($field->allowDuplicateRelations);
        $this->assertFalse($field->reorderAttachables);
        $this->assertEquals('Post tags management', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    public function test_morph_to_many_field_methods_exist(): void
    {
        $field = MorphToMany::make('Tags');

        // Test that the required methods exist
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
        $this->assertTrue(method_exists($field, 'getAttachableModels'));
    }

    public function test_morph_to_many_field_component_name(): void
    {
        $field = MorphToMany::make('Tags');

        $this->assertEquals('MorphToManyField', $field->component);
    }

    public function test_morph_to_many_field_attribute_guessing(): void
    {
        // Test various field names and their attribute guessing
        $testCases = [
            ['Post Tags', 'post_tags'],
            ['User Categories', 'user_categories'],
            ['Product Labels', 'product_labels'],
            ['Tags', 'tags'],
        ];

        foreach ($testCases as [$name, $expectedAttribute]) {
            $field = MorphToMany::make($name);
            $this->assertEquals($expectedAttribute, $field->attribute);
            $this->assertEquals($expectedAttribute, $field->relationshipName);
        }
    }

    public function test_morph_to_many_field_polymorphic_properties(): void
    {
        $field = MorphToMany::make('Tags')
            ->morphType('taggable_type')
            ->morphId('taggable_id');

        // Test that polymorphic properties are set correctly
        $this->assertEquals('taggable_type', $field->morphType);
        $this->assertEquals('taggable_id', $field->morphId);

        // Test that these are included in meta
        $meta = $field->meta();
        $this->assertEquals('taggable_type', $meta['morphType']);
        $this->assertEquals('taggable_id', $meta['morphId']);
    }

    public function test_morph_to_many_field_make_method_signature(): void
    {
        // Test basic make method
        $field1 = MorphToMany::make('Tags');
        $this->assertEquals('Tags', $field1->name);
        $this->assertEquals('tags', $field1->attribute);

        // Test make method with attribute
        $field2 = MorphToMany::make('Tags', 'post_tags');
        $this->assertEquals('Tags', $field2->name);
        $this->assertEquals('post_tags', $field2->attribute);

        // Test make method with callback
        $callback = function ($value) {
            return $value;
        };
        $field3 = MorphToMany::make('Tags', 'tags', $callback);
        $this->assertEquals('Tags', $field3->name);
        $this->assertEquals('tags', $field3->attribute);
        $this->assertEquals($callback, $field3->resolveCallback);
    }
}

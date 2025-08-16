<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\BelongsToMany;
use JTD\AdminPanel\Tests\TestCase;

/**
 * BelongsToMany Field Unit Test.
 *
 * Tests the BelongsToMany field class functionality including field creation,
 * configuration, resolution, and Nova v5 compatibility.
 */
class BelongsToManyFieldTest extends TestCase
{
    public function test_belongs_to_many_field_creation(): void
    {
        $field = BelongsToMany::make('Roles');

        $this->assertEquals('Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals('BelongsToManyField', $field->component);
        $this->assertEquals('roles', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Roles', $field->resourceClass);
    }

    public function test_belongs_to_many_field_with_custom_attribute(): void
    {
        $field = BelongsToMany::make('User Roles', 'user_roles');

        $this->assertEquals('User Roles', $field->name);
        $this->assertEquals('user_roles', $field->attribute);
        $this->assertEquals('user_roles', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\UserRoles', $field->resourceClass);
    }

    public function test_belongs_to_many_field_with_resource_class(): void
    {
        $field = BelongsToMany::make('Roles', 'roles', 'App\\Nova\\RoleResource');

        $this->assertEquals('Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals('App\\Nova\\RoleResource', $field->resourceClass);
    }

    public function test_belongs_to_many_field_default_properties(): void
    {
        $field = BelongsToMany::make('Roles');

        $this->assertEquals('roles', $field->relationshipName);
        $this->assertEquals('App\\AdminPanel\\Resources\\Roles', $field->resourceClass);
        $this->assertNull($field->table);
        $this->assertNull($field->foreignPivotKey);
        $this->assertNull($field->relatedPivotKey);
        $this->assertNull($field->parentKey);
        $this->assertNull($field->relatedKey);
        $this->assertFalse($field->searchable);
        $this->assertFalse($field->withSubtitles);
        $this->assertFalse($field->collapsable);
        $this->assertFalse($field->collapsedByDefault);
        $this->assertFalse($field->showCreateRelationButton);
        $this->assertNull($field->modalSize);
        $this->assertTrue($field->reorderAttachables);
        $this->assertFalse($field->allowDuplicateRelations);
        $this->assertEquals(15, $field->perPage);
        $this->assertEmpty($field->pivotFields);
        $this->assertEmpty($field->pivotComputedFields);
        $this->assertEmpty($field->pivotActions);
    }

    public function test_belongs_to_many_field_resource_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->resource('App\\Models\\RoleResource');

        $this->assertEquals('App\\Models\\RoleResource', $field->resourceClass);
    }

    public function test_belongs_to_many_field_relationship_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->relationship('userRoles');

        $this->assertEquals('userRoles', $field->relationshipName);
    }

    public function test_belongs_to_many_field_table_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->table('role_user');

        $this->assertEquals('role_user', $field->table);
    }

    public function test_belongs_to_many_field_foreign_pivot_key_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->foreignPivotKey('user_id');

        $this->assertEquals('user_id', $field->foreignPivotKey);
    }

    public function test_belongs_to_many_field_related_pivot_key_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->relatedPivotKey('role_id');

        $this->assertEquals('role_id', $field->relatedPivotKey);
    }

    public function test_belongs_to_many_field_parent_key_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->parentKey('id');

        $this->assertEquals('id', $field->parentKey);
    }

    public function test_belongs_to_many_field_related_key_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->relatedKey('id');

        $this->assertEquals('id', $field->relatedKey);
    }

    public function test_belongs_to_many_field_searchable_configuration(): void
    {
        $field = BelongsToMany::make('Roles')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_belongs_to_many_field_searchable_with_callback(): void
    {
        $field = BelongsToMany::make('Roles')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);
    }

    public function test_belongs_to_many_field_searchable_false(): void
    {
        $field = BelongsToMany::make('Roles')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_belongs_to_many_field_with_subtitles(): void
    {
        $field = BelongsToMany::make('Roles')->withSubtitles();

        $this->assertTrue($field->withSubtitles);
    }

    public function test_belongs_to_many_field_collapsable(): void
    {
        $field = BelongsToMany::make('Roles')->collapsable();

        $this->assertTrue($field->collapsable);
    }

    public function test_belongs_to_many_field_collapsed_by_default(): void
    {
        $field = BelongsToMany::make('Roles')->collapsedByDefault();

        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->collapsable); // Should also be collapsable
    }

    public function test_belongs_to_many_field_show_create_relation_button(): void
    {
        $field = BelongsToMany::make('Roles')->showCreateRelationButton();

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_belongs_to_many_field_show_create_relation_button_with_callback(): void
    {
        $field = BelongsToMany::make('Roles')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);
    }

    public function test_belongs_to_many_field_hide_create_relation_button(): void
    {
        $field = BelongsToMany::make('Roles')
            ->showCreateRelationButton()
            ->hideCreateRelationButton();

        $this->assertFalse($field->showCreateRelationButton);
    }

    public function test_belongs_to_many_field_modal_size(): void
    {
        $field = BelongsToMany::make('Roles')->modalSize('large');

        $this->assertEquals('large', $field->modalSize);
    }

    public function test_belongs_to_many_field_dont_reorder_attachables(): void
    {
        $field = BelongsToMany::make('Roles')->dontReorderAttachables();

        $this->assertFalse($field->reorderAttachables);
    }

    public function test_belongs_to_many_field_allow_duplicate_relations(): void
    {
        $field = BelongsToMany::make('Roles')->allowDuplicateRelations();

        $this->assertTrue($field->allowDuplicateRelations);
    }

    public function test_belongs_to_many_field_allow_duplicate_relations_false(): void
    {
        $field = BelongsToMany::make('Roles')->allowDuplicateRelations(false);

        $this->assertFalse($field->allowDuplicateRelations);
    }

    public function test_belongs_to_many_field_relatable_query_using(): void
    {
        $queryCallback = function ($request, $query) {
            return $query->where('active', true);
        };

        $field = BelongsToMany::make('Roles')->relatableQueryUsing($queryCallback);

        $this->assertEquals($queryCallback, $field->relatableQueryCallback);
    }

    public function test_belongs_to_many_field_pivot_fields(): void
    {
        $pivotFields = ['created_at', 'updated_at', 'is_primary'];

        $field = BelongsToMany::make('Roles')->fields($pivotFields);

        $this->assertEquals($pivotFields, $field->pivotFields);
    }

    public function test_belongs_to_many_field_pivot_computed_fields(): void
    {
        $computedFields = ['full_name', 'display_name'];

        $field = BelongsToMany::make('Roles')->computedFields($computedFields);

        $this->assertEquals($computedFields, $field->pivotComputedFields);
    }

    public function test_belongs_to_many_field_pivot_actions(): void
    {
        $actions = ['promote', 'demote'];

        $field = BelongsToMany::make('Roles')->actions($actions);

        $this->assertEquals($actions, $field->pivotActions);
    }

    public function test_belongs_to_many_field_is_only_shown_on_detail_by_default(): void
    {
        $field = BelongsToMany::make('Roles');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_belongs_to_many_field_can_be_configured_for_different_views(): void
    {
        $field = BelongsToMany::make('Roles')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_belongs_to_many_field_fill_with_custom_callback(): void
    {
        $request = new Request(['roles' => 'test']);
        $model = new \stdClass;
        $callbackCalled = false;

        $field = BelongsToMany::make('Roles');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('roles', $attribute);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
    }

    public function test_belongs_to_many_field_meta_includes_all_properties(): void
    {
        $field = BelongsToMany::make('Roles')
            ->resource('App\\Models\\RoleResource')
            ->relationship('userRoles')
            ->table('role_user')
            ->foreignPivotKey('user_id')
            ->relatedPivotKey('role_id')
            ->parentKey('id')
            ->relatedKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->dontReorderAttachables()
            ->allowDuplicateRelations()
            ->fields(['created_at', 'is_primary'])
            ->computedFields(['full_name'])
            ->actions(['promote']);

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('table', $meta);
        $this->assertArrayHasKey('foreignPivotKey', $meta);
        $this->assertArrayHasKey('relatedPivotKey', $meta);
        $this->assertArrayHasKey('parentKey', $meta);
        $this->assertArrayHasKey('relatedKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('withSubtitles', $meta);
        $this->assertArrayHasKey('collapsable', $meta);
        $this->assertArrayHasKey('collapsedByDefault', $meta);
        $this->assertArrayHasKey('showCreateRelationButton', $meta);
        $this->assertArrayHasKey('modalSize', $meta);
        $this->assertArrayHasKey('reorderAttachables', $meta);
        $this->assertArrayHasKey('allowDuplicateRelations', $meta);
        $this->assertArrayHasKey('perPage', $meta);
        $this->assertArrayHasKey('pivotFields', $meta);
        $this->assertArrayHasKey('pivotComputedFields', $meta);
        $this->assertArrayHasKey('pivotActions', $meta);

        $this->assertEquals('App\\Models\\RoleResource', $meta['resourceClass']);
        $this->assertEquals('userRoles', $meta['relationshipName']);
        $this->assertEquals('role_user', $meta['table']);
        $this->assertEquals('user_id', $meta['foreignPivotKey']);
        $this->assertEquals('role_id', $meta['relatedPivotKey']);
        $this->assertEquals('id', $meta['parentKey']);
        $this->assertEquals('id', $meta['relatedKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertTrue($meta['collapsedByDefault']);
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertFalse($meta['reorderAttachables']);
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertEquals(15, $meta['perPage']);
        $this->assertEquals(['created_at', 'is_primary'], $meta['pivotFields']);
        $this->assertEquals(['full_name'], $meta['pivotComputedFields']);
        $this->assertEquals(['promote'], $meta['pivotActions']);
    }

    public function test_belongs_to_many_field_json_serialization(): void
    {
        $field = BelongsToMany::make('User Roles')
            ->resource('App\\Resources\\RoleResource')
            ->table('role_user')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->fields(['created_at', 'is_primary'])
            ->help('Manage user roles');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Roles', $json['name']);
        $this->assertEquals('user_roles', $json['attribute']);
        $this->assertEquals('BelongsToManyField', $json['component']);
        $this->assertEquals('App\\Resources\\RoleResource', $json['resourceClass']);
        $this->assertEquals('role_user', $json['table']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals(['created_at', 'is_primary'], $json['pivotFields']);
        $this->assertEquals('Manage user roles', $json['helpText']);
    }

    public function test_belongs_to_many_field_complex_configuration(): void
    {
        $field = BelongsToMany::make('User Permissions')
            ->resource('App\\Resources\\PermissionResource')
            ->relationship('userPermissions')
            ->table('user_permissions')
            ->foreignPivotKey('user_id')
            ->relatedPivotKey('permission_id')
            ->parentKey('id')
            ->relatedKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('xl')
            ->allowDuplicateRelations()
            ->fields(['granted_at', 'granted_by', 'is_active'])
            ->computedFields(['permission_level'])
            ->actions(['grant', 'revoke']);

        // Test all configurations are set
        $this->assertEquals('App\\Resources\\PermissionResource', $field->resourceClass);
        $this->assertEquals('userPermissions', $field->relationshipName);
        $this->assertEquals('user_permissions', $field->table);
        $this->assertEquals('user_id', $field->foreignPivotKey);
        $this->assertEquals('permission_id', $field->relatedPivotKey);
        $this->assertEquals('id', $field->parentKey);
        $this->assertEquals('id', $field->relatedKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('xl', $field->modalSize);
        $this->assertTrue($field->allowDuplicateRelations);
        $this->assertEquals(['granted_at', 'granted_by', 'is_active'], $field->pivotFields);
        $this->assertEquals(['permission_level'], $field->pivotComputedFields);
        $this->assertEquals(['grant', 'revoke'], $field->pivotActions);
    }

    public function test_belongs_to_many_field_methods_exist(): void
    {
        $field = BelongsToMany::make('Roles');

        // Test that the required methods exist
        $this->assertTrue(method_exists($field, 'getRelatedModels'));
        $this->assertTrue(method_exists($field, 'getAttachableModels'));
        $this->assertTrue(method_exists($field, 'attachModels'));
        $this->assertTrue(method_exists($field, 'detachModels'));
        $this->assertTrue(method_exists($field, 'updatePivot'));
    }

    public function test_belongs_to_many_field_guesses_resource_class_correctly(): void
    {
        $field = BelongsToMany::make('User Roles', 'user_roles');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserRoles', $field->resourceClass);
    }

    public function test_belongs_to_many_field_with_resolve_callback(): void
    {
        $callbackCalled = false;
        $resolveCallback = function ($value, $resource, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;

            return $value;
        };

        $field = BelongsToMany::make('Roles', 'roles', $resolveCallback);

        $this->assertEquals($resolveCallback, $field->resolveCallback);
    }

    public function test_belongs_to_many_field_nova_style_make_method(): void
    {
        // Test Nova-style syntax with resource class as third parameter
        $field = BelongsToMany::make('Roles', 'roles', 'App\\Nova\\RoleResource');

        $this->assertEquals('Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals('App\\Nova\\RoleResource', $field->resourceClass);
    }

    public function test_belongs_to_many_field_make_method_with_callback(): void
    {
        // Test with callback as third parameter
        $callback = function ($value) {
            return $value;
        };

        $field = BelongsToMany::make('Roles', 'roles', $callback);

        $this->assertEquals('Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_belongs_to_many_field_supports_field_chaining(): void
    {
        $field = BelongsToMany::make('Roles')
            ->resource('App\\Nova\\RoleResource')
            ->relationship('userRoles')
            ->table('role_user')
            ->foreignPivotKey('user_id')
            ->relatedPivotKey('role_id')
            ->parentKey('id')
            ->relatedKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->collapsedByDefault()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->dontReorderAttachables()
            ->allowDuplicateRelations()
            ->fields(['created_at'])
            ->computedFields(['display_name'])
            ->actions(['activate'])
            ->help('User roles management')
            ->showOnIndex();

        $this->assertEquals('App\\Nova\\RoleResource', $field->resourceClass);
        $this->assertEquals('userRoles', $field->relationshipName);
        $this->assertEquals('role_user', $field->table);
        $this->assertEquals('user_id', $field->foreignPivotKey);
        $this->assertEquals('role_id', $field->relatedPivotKey);
        $this->assertEquals('id', $field->parentKey);
        $this->assertEquals('id', $field->relatedKey);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->withSubtitles);
        $this->assertTrue($field->collapsable);
        $this->assertTrue($field->collapsedByDefault);
        $this->assertTrue($field->showCreateRelationButton);
        $this->assertEquals('large', $field->modalSize);
        $this->assertFalse($field->reorderAttachables);
        $this->assertTrue($field->allowDuplicateRelations);
        $this->assertEquals(['created_at'], $field->pivotFields);
        $this->assertEquals(['display_name'], $field->pivotComputedFields);
        $this->assertEquals(['activate'], $field->pivotActions);
        $this->assertEquals('User roles management', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }
}

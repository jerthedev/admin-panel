<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\BelongsToMany;
use JTD\AdminPanel\Tests\Fixtures\Role;
use JTD\AdminPanel\Tests\Fixtures\RoleResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * BelongsToMany Field Integration Test.
 *
 * Tests the complete integration between PHP BelongsToMany field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class BelongsToManyFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test roles
        $adminRole = Role::factory()->create(['id' => 1, 'name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrator role']);
        $editorRole = Role::factory()->create(['id' => 2, 'name' => 'Editor', 'slug' => 'editor', 'description' => 'Editor role']);
        $viewerRole = Role::factory()->create(['id' => 3, 'name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Viewer role']);

        // Create test users
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Assign roles to users with pivot data
        $user1->roles()->attach([
            1 => ['assigned_at' => now(), 'assigned_by' => 'system', 'is_primary' => true],
            2 => ['assigned_at' => now(), 'assigned_by' => 'system', 'is_primary' => false],
        ]);

        $user2->roles()->attach([
            2 => ['assigned_at' => now(), 'assigned_by' => 'admin', 'is_primary' => true],
            3 => ['assigned_at' => now(), 'assigned_by' => 'admin', 'is_primary' => false],
        ]);

        // User 3 has no roles
    }

    /** @test */
    public function it_creates_belongs_to_many_field_with_nova_syntax(): void
    {
        $field = BelongsToMany::make('Roles');

        $this->assertEquals('Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals('roles', $field->relationshipName);
    }

    /** @test */
    public function it_creates_belongs_to_many_field_with_custom_resource(): void
    {
        $field = BelongsToMany::make('User Roles', 'roles', RoleResource::class);

        $this->assertEquals('User Roles', $field->name);
        $this->assertEquals('roles', $field->attribute);
        $this->assertEquals('roles', $field->relationshipName);
        $this->assertEquals(RoleResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = BelongsToMany::make('Roles')
            ->resource(RoleResource::class)
            ->relationship('roles')
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
            ->allowDuplicateRelations()
            ->fields(['assigned_at', 'assigned_by', 'is_primary'])
            ->computedFields(['role_level'])
            ->actions(['promote', 'demote']);

        $meta = $field->meta();

        $this->assertEquals(RoleResource::class, $meta['resourceClass']);
        $this->assertEquals('roles', $meta['relationshipName']);
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
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertEquals(['assigned_at', 'assigned_by', 'is_primary'], $meta['pivotFields']);
        $this->assertEquals(['role_level'], $meta['pivotComputedFields']);
        $this->assertEquals(['promote', 'demote'], $meta['pivotActions']);
    }

    /** @test */
    public function it_resolves_belongs_to_many_relationship_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles

        $field = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('count', $field->value);
        $this->assertArrayHasKey('resource_id', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('pivot_fields', $field->value);
        $this->assertArrayHasKey('pivot_computed_fields', $field->value);
        $this->assertArrayHasKey('pivot_actions', $field->value);

        $this->assertEquals(2, $field->value['count']); // User has 2 roles
        $this->assertEquals(1, $field->value['resource_id']);
        $this->assertEquals(RoleResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $user = User::find(3); // User with no roles

        $field = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertEquals(0, $field->value['count']);
        $this->assertEquals(3, $field->value['resource_id']);
        $this->assertEquals(RoleResource::class, $field->value['resource_class']);
    }

    /** @test */
    public function it_gets_related_models_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles

        $field = BelongsToMany::make('Roles');
        $result = $field->getRelatedModels(new Request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('Admin', $result['data'][0]->name);
        $this->assertEquals('Editor', $result['data'][1]->name);
    }

    /** @test */
    public function it_gets_related_models_with_search(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles
        $request = new Request(['search' => 'Admin']);

        $field = BelongsToMany::make('Roles')->searchable();
        $result = $field->getRelatedModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Admin', $result['data'][0]->name);
    }

    /** @test */
    public function it_gets_attachable_models_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles (Admin, Editor)

        $field = BelongsToMany::make('Roles');
        $result = $field->getAttachableModels(new Request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']); // Only Viewer role is attachable
        $this->assertEquals('Viewer', $result['data'][0]->name);
    }

    /** @test */
    public function it_gets_attachable_models_with_duplicates_allowed(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles

        $field = BelongsToMany::make('Roles')->allowDuplicateRelations();
        $result = $field->getAttachableModels(new Request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['data']); // All roles are attachable when duplicates allowed
    }

    /** @test */
    public function it_gets_attachable_models_with_search(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles
        $request = new Request(['search' => 'Viewer']);

        $field = BelongsToMany::make('Roles');
        $result = $field->getAttachableModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Viewer', $result['data'][0]->name);
    }

    /** @test */
    public function it_gets_attachable_models_with_custom_query(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles
        $request = new Request;

        $field = BelongsToMany::make('Roles')->relatableQueryUsing(function ($request, $query) {
            return $query->where('is_active', true);
        });

        $result = $field->getAttachableModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        // Should only return active roles that aren't already attached
        $this->assertCount(1, $result['data']);
    }

    /** @test */
    public function it_attaches_models_correctly(): void
    {
        $user = User::find(3); // User with no roles

        $field = BelongsToMany::make('Roles');
        $field->attachModels(new Request, $user, [1, 2], ['assigned_by' => 'test']);

        $user->refresh();
        $this->assertCount(2, $user->roles);
        $this->assertEquals('Admin', $user->roles->first()->name);
        $this->assertEquals('test', $user->roles->first()->pivot->assigned_by);
    }

    /** @test */
    public function it_detaches_models_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles

        $field = BelongsToMany::make('Roles');
        $field->detachModels(new Request, $user, [1]); // Detach Admin role

        $user->refresh();
        $this->assertCount(1, $user->roles);
        $this->assertEquals('Editor', $user->roles->first()->name);
    }

    /** @test */
    public function it_updates_pivot_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles

        $field = BelongsToMany::make('Roles');
        $field->updatePivot(new Request, $user, 1, ['is_primary' => false]);

        $user->refresh();
        $adminRole = $user->roles->where('id', 1)->first();
        $this->assertEquals(0, $adminRole->pivot->is_primary); // SQLite stores boolean as 0/1
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['roles' => 'test']);
        $user = User::find(1);
        $callbackCalled = false;

        $field = BelongsToMany::make('Roles');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('roles', $attribute);
            $this->assertInstanceOf(User::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $user);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = BelongsToMany::make('Roles')
            ->resource(RoleResource::class)
            ->relationship('roles')
            ->table('role_user')
            ->foreignPivotKey('user_id')
            ->relatedPivotKey('role_id')
            ->parentKey('id')
            ->relatedKey('id')
            ->searchable()
            ->withSubtitles()
            ->collapsable()
            ->showCreateRelationButton()
            ->modalSize('large')
            ->allowDuplicateRelations()
            ->fields(['assigned_at', 'assigned_by'])
            ->computedFields(['role_level'])
            ->actions(['promote']);

        $meta = $field->meta();

        // Check all required meta fields
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

        // Check values
        $this->assertEquals(RoleResource::class, $meta['resourceClass']);
        $this->assertEquals('roles', $meta['relationshipName']);
        $this->assertEquals('role_user', $meta['table']);
        $this->assertEquals('user_id', $meta['foreignPivotKey']);
        $this->assertEquals('role_id', $meta['relatedPivotKey']);
        $this->assertEquals('id', $meta['parentKey']);
        $this->assertEquals('id', $meta['relatedKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['withSubtitles']);
        $this->assertTrue($meta['collapsable']);
        $this->assertFalse($meta['collapsedByDefault']); // Not set, so should be false
        $this->assertTrue($meta['showCreateRelationButton']);
        $this->assertEquals('large', $meta['modalSize']);
        $this->assertTrue($meta['reorderAttachables']); // Default value
        $this->assertTrue($meta['allowDuplicateRelations']);
        $this->assertEquals(15, $meta['perPage']);
        $this->assertEquals(['assigned_at', 'assigned_by'], $meta['pivotFields']);
        $this->assertEquals(['role_level'], $meta['pivotComputedFields']);
        $this->assertEquals(['promote'], $meta['pivotActions']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = BelongsToMany::make('User Roles')
            ->resource(RoleResource::class)
            ->table('role_user')
            ->searchable()
            ->collapsable()
            ->showCreateRelationButton()
            ->fields(['assigned_at', 'is_primary'])
            ->help('Manage user roles');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Roles', $json['name']);
        $this->assertEquals('user_roles', $json['attribute']);
        $this->assertEquals('BelongsToManyField', $json['component']);
        $this->assertEquals(RoleResource::class, $json['resourceClass']);
        $this->assertEquals('role_user', $json['table']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['collapsable']);
        $this->assertTrue($json['showCreateRelationButton']);
        $this->assertEquals(['assigned_at', 'is_primary'], $json['pivotFields']);
        $this->assertEquals('Manage user roles', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = BelongsToMany::make('User Roles', 'user_roles');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserRoles', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = BelongsToMany::make('Roles');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
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

    /** @test */
    public function it_works_with_complex_pivot_relationships(): void
    {
        // Test with different users and their role assignments
        $user1 = User::with('roles')->find(1); // 2 roles
        $user2 = User::with('roles')->find(2); // 2 roles
        $user3 = User::with('roles')->find(3); // 0 roles

        // Test user 1
        $field1 = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field1->resolve($user1);

        $this->assertEquals(2, $field1->value['count']);
        $this->assertEquals(RoleResource::class, $field1->value['resource_class']);

        // Test user 2
        $field2 = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field2->resolve($user2);

        $this->assertEquals(2, $field2->value['count']);

        // Test user 3
        $field3 = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field3->resolve($user3);

        $this->assertEquals(0, $field3->value['count']);
    }

    /** @test */
    public function it_supports_conditional_searchable(): void
    {
        // Test with callable searchable
        $field = BelongsToMany::make('Roles')->searchable(function () {
            return true;
        });

        $this->assertTrue($field->searchable);

        // Test with callable returning false
        $field2 = BelongsToMany::make('Roles')->searchable(function () {
            return false;
        });

        $this->assertFalse($field2->searchable);
    }

    /** @test */
    public function it_supports_conditional_show_create_relation_button(): void
    {
        // Test with callable showCreateRelationButton
        $field = BelongsToMany::make('Roles')->showCreateRelationButton(function () {
            return true;
        });

        $this->assertTrue($field->showCreateRelationButton);

        // Test with callable returning false
        $field2 = BelongsToMany::make('Roles')->showCreateRelationButton(function () {
            return false;
        });

        $this->assertFalse($field2->showCreateRelationButton);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        $user = User::with('roles')->find(1); // User with 2 roles
        $request = new Request(['perPage' => 1, 'page' => 1]);

        $field = BelongsToMany::make('Roles');
        $result = $field->getRelatedModels($request, $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(1, $result['data']); // First page with 1 item
        $this->assertEquals(1, $result['meta']['current_page']);
        $this->assertEquals(2, $result['meta']['last_page']);
        $this->assertEquals(1, $result['meta']['per_page']);
        $this->assertEquals(2, $result['meta']['total']);
    }

    /** @test */
    public function it_handles_soft_deleted_relationships(): void
    {
        // Soft delete a role
        $role = Role::find(1);
        $role->delete();

        $user = User::with('roles')->find(1);

        $field = BelongsToMany::make('Roles', 'roles', RoleResource::class);
        $field->resolve($user);

        // Should show 1 role instead of 2 (excluding soft-deleted)
        $this->assertEquals(1, $field->value['count']);
    }
}

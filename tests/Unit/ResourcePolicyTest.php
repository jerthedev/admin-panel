<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use JTD\AdminPanel\Policies\ResourcePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Test implementation of ResourcePolicy for testing purposes.
 */
class TestResourcePolicy extends ResourcePolicy
{
    // Expose protected methods for testing
    public function testHasRole($user, string $role): bool
    {
        return $this->hasRole($user, $role);
    }

    public function testHasPermission($user, string $permission): bool
    {
        return $this->hasPermission($user, $permission);
    }

    public function testOwns($user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function testIsAdmin($user): bool
    {
        return $this->isAdmin($user);
    }

    public function testIsSuperAdmin($user): bool
    {
        return $this->isSuperAdmin($user);
    }
}

/**
 * Mock User class for testing.
 */
class MockUser
{
    public int $id;
    public ?string $role = null;
    public array $permissions = [];

    public function __construct(int $id, ?string $role = null, array $permissions = [])
    {
        $this->id = $id;
        $this->role = $role;
        $this->permissions = $permissions;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function can(string $permission): bool
    {
        return $this->hasPermission($permission);
    }
}

/**
 * Mock Model class for testing.
 */
class MockModel extends Model
{
    protected $fillable = ['user_id', 'owner_id', 'created_by', 'name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setRawAttributes($attributes);
    }
}

/**
 * ResourcePolicy Test Class
 */
class ResourcePolicyTest extends TestCase
{
    private TestResourcePolicy $policy;
    private MockUser $user;
    private MockModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TestResourcePolicy();
        $this->user = new MockUser(1);
        $this->model = new MockModel(['id' => 1, 'name' => 'Test Model']);
    }

    // ========================================
    // Basic CRUD Authorization Tests
    // ========================================

    public function test_view_any_returns_true_by_default(): void
    {
        $result = $this->policy->viewAny($this->user);

        $this->assertTrue($result);
    }

    public function test_view_returns_true_by_default(): void
    {
        $result = $this->policy->view($this->user, $this->model);

        $this->assertTrue($result);
    }

    public function test_create_returns_true_by_default(): void
    {
        $result = $this->policy->create($this->user);

        $this->assertTrue($result);
    }

    public function test_update_returns_true_by_default(): void
    {
        $result = $this->policy->update($this->user, $this->model);

        $this->assertTrue($result);
    }

    public function test_delete_returns_true_by_default(): void
    {
        $result = $this->policy->delete($this->user, $this->model);

        $this->assertTrue($result);
    }

    public function test_restore_returns_true_by_default(): void
    {
        $result = $this->policy->restore($this->user, $this->model);

        $this->assertTrue($result);
    }

    public function test_force_delete_returns_true_by_default(): void
    {
        $result = $this->policy->forceDelete($this->user, $this->model);

        $this->assertTrue($result);
    }

    // ========================================
    // Relationship Authorization Tests
    // ========================================

    public function test_attach_delegates_to_update(): void
    {
        $result = $this->policy->attach($this->user, $this->model);

        $this->assertTrue($result);
    }

    public function test_detach_delegates_to_update(): void
    {
        $result = $this->policy->detach($this->user, $this->model);

        $this->assertTrue($result);
    }

    // ========================================
    // Field-Level Authorization Tests
    // ========================================

    public function test_view_field_delegates_to_view(): void
    {
        $result = $this->policy->viewField($this->user, $this->model, 'name');

        $this->assertTrue($result);
    }

    public function test_update_field_delegates_to_update(): void
    {
        $result = $this->policy->updateField($this->user, $this->model, 'name');

        $this->assertTrue($result);
    }

    // ========================================
    // Action Authorization Tests
    // ========================================

    public function test_run_action_delegates_to_update(): void
    {
        $result = $this->policy->runAction($this->user, $this->model, 'publish');

        $this->assertTrue($result);
    }

    public function test_export_delegates_to_view_any(): void
    {
        $result = $this->policy->export($this->user);

        $this->assertTrue($result);
    }

    public function test_import_delegates_to_create(): void
    {
        $result = $this->policy->import($this->user);

        $this->assertTrue($result);
    }

    // ========================================
    // Field Configuration Tests
    // ========================================

    public function test_get_hidden_fields_returns_empty_array_by_default(): void
    {
        $result = $this->policy->getHiddenFields($this->user, $this->model);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_readonly_fields_returns_empty_array_by_default(): void
    {
        $result = $this->policy->getReadonlyFields($this->user, $this->model);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_hidden_actions_returns_empty_array_by_default(): void
    {
        $result = $this->policy->getHiddenActions($this->user, $this->model);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ========================================
    // Navigation Authorization Tests
    // ========================================

    public function test_view_in_navigation_delegates_to_view_any(): void
    {
        $result = $this->policy->viewInNavigation($this->user);

        $this->assertTrue($result);
    }

    public function test_view_in_search_delegates_to_view_any(): void
    {
        $result = $this->policy->viewInSearch($this->user);

        $this->assertTrue($result);
    }

    // ========================================
    // Authorization Message Tests
    // ========================================

    public function test_get_authorization_message_returns_correct_messages(): void
    {
        $this->assertEquals(
            'You are not authorized to view this resource.',
            $this->policy->getAuthorizationMessage('viewAny')
        );

        $this->assertEquals(
            'You are not authorized to view this item.',
            $this->policy->getAuthorizationMessage('view')
        );

        $this->assertEquals(
            'You are not authorized to create new items.',
            $this->policy->getAuthorizationMessage('create')
        );

        $this->assertEquals(
            'You are not authorized to update this item.',
            $this->policy->getAuthorizationMessage('update')
        );

        $this->assertEquals(
            'You are not authorized to delete this item.',
            $this->policy->getAuthorizationMessage('delete')
        );

        $this->assertNull($this->policy->getAuthorizationMessage('unknown'));
    }

    // ========================================
    // Helper Method Tests
    // ========================================

    public function test_has_role_with_role_property(): void
    {
        $user = new MockUser(1, 'admin');

        $this->assertTrue($this->policy->testHasRole($user, 'admin'));
        $this->assertFalse($this->policy->testHasRole($user, 'user'));
    }

    public function test_has_permission_with_permissions_array(): void
    {
        $user = new MockUser(1, null, ['edit-posts', 'delete-posts']);

        $this->assertTrue($this->policy->testHasPermission($user, 'edit-posts'));
        $this->assertFalse($this->policy->testHasPermission($user, 'create-posts'));
    }

    public function test_owns_with_user_id_property(): void
    {
        $model = new MockModel(['user_id' => 1]);

        $this->assertTrue($this->policy->testOwns($this->user, $model));

        $model = new MockModel(['user_id' => 2]);
        $this->assertFalse($this->policy->testOwns($this->user, $model));
    }

    public function test_owns_with_owner_id_property(): void
    {
        $model = new MockModel(['owner_id' => 1]);

        $this->assertTrue($this->policy->testOwns($this->user, $model));

        $model = new MockModel(['owner_id' => 2]);
        $this->assertFalse($this->policy->testOwns($this->user, $model));
    }

    public function test_owns_with_created_by_property(): void
    {
        $model = new MockModel(['created_by' => 1]);

        $this->assertTrue($this->policy->testOwns($this->user, $model));

        $model = new MockModel(['created_by' => 2]);
        $this->assertFalse($this->policy->testOwns($this->user, $model));
    }

    public function test_is_admin_with_admin_role(): void
    {
        $user = new MockUser(1, 'admin');
        $this->assertTrue($this->policy->testIsAdmin($user));

        $user = new MockUser(1, 'administrator');
        $this->assertTrue($this->policy->testIsAdmin($user));

        $user = new MockUser(1, 'user');
        $this->assertFalse($this->policy->testIsAdmin($user));
    }

    public function test_is_admin_with_admin_permission(): void
    {
        $user = new MockUser(1, null, ['admin.*']);
        $this->assertTrue($this->policy->testIsAdmin($user));

        $user = new MockUser(1, null, ['user.*']);
        $this->assertFalse($this->policy->testIsAdmin($user));
    }

    public function test_is_super_admin_with_super_admin_role(): void
    {
        $user = new MockUser(1, 'super-admin');
        $this->assertTrue($this->policy->testIsSuperAdmin($user));

        $user = new MockUser(1, 'superadmin');
        $this->assertTrue($this->policy->testIsSuperAdmin($user));

        $user = new MockUser(1, 'admin');
        $this->assertFalse($this->policy->testIsSuperAdmin($user));
    }

    public function test_is_super_admin_with_super_admin_permission(): void
    {
        $user = new MockUser(1, null, ['super-admin.*']);
        $this->assertTrue($this->policy->testIsSuperAdmin($user));

        $user = new MockUser(1, null, ['admin.*']);
        $this->assertFalse($this->policy->testIsSuperAdmin($user));
    }
}

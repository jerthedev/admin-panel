<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JTD\AdminPanel\Policies\ResourcePolicy;
use JTD\AdminPanel\Resources\Resource;
use PHPUnit\Framework\TestCase;

/**
 * Mock User class for testing.
 */
class MockUserForIntegration
{
    public int $id;
    public ?string $role = null;

    public function __construct(int $id, ?string $role = null)
    {
        $this->id = $id;
        $this->role = $role;
    }
}

/**
 * Mock Model class for testing.
 */
class MockModelForIntegration extends Model
{
    protected $fillable = ['id', 'name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setRawAttributes($attributes);
    }
}

/**
 * Test Policy class for testing.
 */
class TestPolicy extends ResourcePolicy
{
    public function viewAny($user): bool
    {
        return $user->role === 'admin';
    }

    public function view($user, Model $model): bool
    {
        return $user->role === 'admin' || $user->id === 1;
    }

    public function create($user): bool
    {
        return $user->role === 'admin';
    }

    public function update($user, Model $model): bool
    {
        return $user->role === 'admin';
    }

    public function delete($user, Model $model): bool
    {
        return $user->role === 'admin';
    }
}

/**
 * Test Resource class for testing.
 */
class TestResourceWithPolicy extends Resource
{
    public static string $model = MockModelForIntegration::class;
    public static ?string $policy = TestPolicy::class;

    public function fields(Request $request): array
    {
        return [];
    }
}

/**
 * Test Resource class without policy for testing.
 */
class TestResourceWithoutPolicy extends Resource
{
    public static string $model = MockModelForIntegration::class;

    public function fields(Request $request): array
    {
        return [];
    }
}

/**
 * ResourcePolicyIntegration Test Class
 */
class ResourcePolicyIntegrationTest extends TestCase
{
    private TestResourceWithPolicy $resourceWithPolicy;
    private TestResourceWithoutPolicy $resourceWithoutPolicy;
    private MockModelForIntegration $model;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new MockModelForIntegration(['id' => 1, 'name' => 'Test Model']);
        $this->resourceWithPolicy = new TestResourceWithPolicy($this->model);
        $this->resourceWithoutPolicy = new TestResourceWithoutPolicy($this->model);
        $this->request = new Request();
    }

    // ========================================
    // Policy Resolution Tests
    // ========================================

    public function test_policy_returns_configured_policy(): void
    {
        $policy = TestResourceWithPolicy::policy();

        $this->assertInstanceOf(TestPolicy::class, $policy);
    }

    public function test_policy_returns_null_when_no_policy_configured(): void
    {
        $policy = TestResourceWithoutPolicy::policy();

        $this->assertNull($policy);
    }

    public function test_guess_policy_class_returns_correct_class_name(): void
    {
        $reflection = new \ReflectionClass(TestResourceWithPolicy::class);
        $method = $reflection->getMethod('guessPolicyClass');
        $method->setAccessible(true);

        $result = $method->invoke(null);

        $this->assertEquals('App\\Policies\\TestResourcePolicy', $result);
    }

    // ========================================
    // Authorization with Policy Tests
    // ========================================

    public function test_check_policy_returns_true_when_no_policy(): void
    {
        $result = $this->resourceWithoutPolicy->checkPolicy($this->request, 'viewAny');

        $this->assertTrue($result);
    }

    public function test_check_policy_returns_false_when_no_user(): void
    {
        $result = $this->resourceWithPolicy->checkPolicy($this->request, 'viewAny');

        $this->assertFalse($result);
    }

    public function test_check_policy_calls_policy_method_with_admin_user(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->checkPolicy($this->request, 'viewAny');

        $this->assertTrue($result);
    }

    public function test_check_policy_calls_policy_method_with_regular_user(): void
    {
        $user = new MockUserForIntegration(1, 'user');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->checkPolicy($this->request, 'viewAny');

        $this->assertFalse($result);
    }

    public function test_check_policy_with_model_argument(): void
    {
        $user = new MockUserForIntegration(1, 'user');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->checkPolicy($this->request, 'view', $this->model);

        $this->assertTrue($result); // User ID 1 should be able to view
    }

    // ========================================
    // Authorization Method Tests
    // ========================================

    public function test_authorized_to_view_any_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToViewAny($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_view_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'user');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToViewWithPolicy($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_create_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToCreateWithPolicy($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_update_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToUpdateWithPolicy($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_delete_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToDeleteWithPolicy($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_restore_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToRestore($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_force_delete_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToForceDelete($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_attach_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToAttach($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_detach_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToDetach($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_run_action_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToRunAction($this->request, 'publish');

        $this->assertTrue($result);
    }

    public function test_authorized_to_export_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToExport($this->request);

        $this->assertTrue($result);
    }

    public function test_authorized_to_import_with_policy(): void
    {
        $user = new MockUserForIntegration(1, 'admin');
        $this->request->setUserResolver(fn() => $user);

        $result = $this->resourceWithPolicy->authorizedToImport($this->request);

        $this->assertTrue($result);
    }

    // ========================================
    // Authorization Denial Tests
    // ========================================

    public function test_authorization_denied_for_regular_user(): void
    {
        $user = new MockUserForIntegration(2, 'user');
        $this->request->setUserResolver(fn() => $user);

        $this->assertFalse($this->resourceWithPolicy->authorizedToViewAny($this->request));
        $this->assertFalse($this->resourceWithPolicy->authorizedToCreateWithPolicy($this->request));
        $this->assertFalse($this->resourceWithPolicy->authorizedToUpdateWithPolicy($this->request));
        $this->assertFalse($this->resourceWithPolicy->authorizedToDeleteWithPolicy($this->request));
    }
}

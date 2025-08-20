<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JTD\AdminPanel\Observers\ResourceObserver;
use JTD\AdminPanel\Resources\Concerns\HasObservers;
use JTD\AdminPanel\Resources\Resource;
use PHPUnit\Framework\TestCase;

/**
 * Mock Model for testing observers.
 */
class MockObserverModel extends Model
{
    protected $fillable = ['id', 'name', 'status'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setRawAttributes($attributes);
    }
}

/**
 * Test Observer class.
 */
class TestResourceObserver extends ResourceObserver
{
    public array $events = [];

    protected function beforeCreate(Model $model): void
    {
        $this->events[] = 'beforeCreate';
    }

    protected function afterCreate(Model $model): void
    {
        $this->events[] = 'afterCreate';
    }

    protected function beforeUpdate(Model $model): void
    {
        $this->events[] = 'beforeUpdate';
    }

    protected function afterUpdate(Model $model): void
    {
        $this->events[] = 'afterUpdate';
    }

    protected function beforeSave(Model $model): void
    {
        $this->events[] = 'beforeSave';
    }

    protected function afterSave(Model $model): void
    {
        $this->events[] = 'afterSave';
    }

    protected function beforeDelete(Model $model): void
    {
        $this->events[] = 'beforeDelete';
    }

    protected function afterDelete(Model $model): void
    {
        $this->events[] = 'afterDelete';
    }

    public function getEvents(): array
    {
        return $this->events;
    }
}

/**
 * Test Resource with observers.
 */
class TestResourceWithObservers extends Resource
{
    use HasObservers;

    public static string $model = MockObserverModel::class;
    public static array $observers = [TestResourceObserver::class];

    public function fields(Request $request): array
    {
        return [];
    }

    public static function uriKey(): string
    {
        return 'test-observer';
    }

    public function title(): string
    {
        return $this->resource->name ?? 'Untitled';
    }
}

/**
 * ResourceObserver Test Class
 */
class ResourceObserverTest extends TestCase
{
    private TestResourceObserver $observer;
    private MockObserverModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = new TestResourceObserver();
        $this->model = new MockObserverModel(['id' => 1, 'name' => 'Test Model', 'status' => 'active']);
    }

    // ========================================
    // Basic Observer Event Tests
    // ========================================

    public function test_observer_handles_creating_event(): void
    {
        $this->observer->creating($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('beforeCreate', $events);
    }

    public function test_observer_handles_created_event(): void
    {
        $this->observer->created($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('afterCreate', $events);
    }

    public function test_observer_handles_updating_event(): void
    {
        $this->observer->updating($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('beforeUpdate', $events);
    }

    public function test_observer_handles_updated_event(): void
    {
        $this->observer->updated($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('afterUpdate', $events);
    }

    public function test_observer_handles_saving_event(): void
    {
        $this->observer->saving($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('beforeSave', $events);
    }

    public function test_observer_handles_saved_event(): void
    {
        $this->observer->saved($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('afterSave', $events);
    }

    public function test_observer_handles_deleting_event(): void
    {
        $this->observer->deleting($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('beforeDelete', $events);
    }

    public function test_observer_handles_deleted_event(): void
    {
        $this->observer->deleted($this->model);

        $events = $this->observer->getEvents();
        $this->assertContains('afterDelete', $events);
    }

    public function test_observer_handles_restoring_event(): void
    {
        $this->observer->restoring($this->model);

        // Should not throw an exception
        $this->assertTrue(true);
    }

    public function test_observer_handles_restored_event(): void
    {
        $this->observer->restored($this->model);

        // Should not throw an exception
        $this->assertTrue(true);
    }

    public function test_observer_handles_force_deleting_event(): void
    {
        $this->observer->forceDeleting($this->model);

        // Should not throw an exception
        $this->assertTrue(true);
    }

    public function test_observer_handles_force_deleted_event(): void
    {
        $this->observer->forceDeleted($this->model);

        // Should not throw an exception
        $this->assertTrue(true);
    }

    public function test_observer_handles_replicating_event(): void
    {
        $this->observer->replicating($this->model);

        // Should not throw an exception
        $this->assertTrue(true);
    }

    // ========================================
    // Observer Helper Method Tests
    // ========================================

    public function test_observer_validate_model_returns_true_by_default(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('validateModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->observer, $this->model);

        $this->assertTrue($result);
    }

    public function test_observer_should_handle_returns_true_by_default(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('shouldHandle');
        $method->setAccessible(true);

        $result = $method->invoke($this->observer, $this->model);

        $this->assertTrue($result);
    }

    public function test_observer_get_current_user_returns_null_without_request(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('getCurrentUser');
        $method->setAccessible(true);

        $result = $method->invoke($this->observer);

        $this->assertNull($result);
    }

    // ========================================
    // HasObservers Trait Tests
    // ========================================

    public function test_resource_has_observers(): void
    {
        $hasObservers = TestResourceWithObservers::hasObservers();

        $this->assertTrue($hasObservers);
    }

    public function test_resource_get_observers_returns_configured_observers(): void
    {
        $observers = TestResourceWithObservers::getObservers();

        $this->assertContains(TestResourceObserver::class, $observers);
    }

    public function test_resource_add_observer(): void
    {
        $originalObservers = TestResourceWithObservers::getObservers();

        TestResourceWithObservers::addObserver('NewObserver');
        $newObservers = TestResourceWithObservers::getObservers();

        $this->assertCount(count($originalObservers) + 1, $newObservers);
        $this->assertContains('NewObserver', $newObservers);

        // Clean up
        TestResourceWithObservers::removeObserver('NewObserver');
    }

    public function test_resource_remove_observer(): void
    {
        TestResourceWithObservers::addObserver('TempObserver');
        TestResourceWithObservers::removeObserver('TempObserver');

        $observers = TestResourceWithObservers::getObservers();

        $this->assertNotContains('TempObserver', $observers);
    }

    public function test_resource_guess_observer_class(): void
    {
        $reflection = new \ReflectionClass(TestResourceWithObservers::class);
        $method = $reflection->getMethod('guessObserverClass');
        $method->setAccessible(true);

        $result = $method->invoke(null);

        $this->assertEquals('App\\Observers\\TestResourceObserver', $result);
    }

    public function test_resource_get_observer_events(): void
    {
        $events = TestResourceWithObservers::getObserverEvents();

        $expectedEvents = [
            'creating', 'created', 'updating', 'updated',
            'saving', 'saved', 'deleting', 'deleted',
            'restoring', 'restored', 'forceDeleting', 'forceDeleted',
            'replicating'
        ];

        foreach ($expectedEvents as $event) {
            $this->assertContains($event, $events);
        }
    }

    public function test_resource_get_observer_stats(): void
    {
        $stats = TestResourceWithObservers::getObserverStats();

        $this->assertArrayHasKey('total_observers', $stats);
        $this->assertArrayHasKey('registered', $stats);
        $this->assertArrayHasKey('events_handled', $stats);
        $this->assertGreaterThan(0, $stats['total_observers']);
    }

    public function test_resource_trigger_observer_event(): void
    {
        // This test verifies the method exists and doesn't throw an exception
        TestResourceWithObservers::triggerObserverEvent('creating', $this->model);

        $this->assertTrue(true);
    }

    public function test_resource_create_observer_throws_exception_for_invalid_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Observer class NonExistentObserver does not exist.');

        TestResourceWithObservers::createObserver('NonExistentObserver');
    }

    public function test_resource_with_observers_trait_methods_exist(): void
    {
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'registerObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'getObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'addObserver'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'removeObserver'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'hasObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'getObserverEvents'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'triggerObserverEvent'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'disableObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'enableObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'withoutObservers'));
        $this->assertTrue(method_exists(TestResourceWithObservers::class, 'getObserverStats'));
    }
}

<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\HasOneThrough;
use JTD\AdminPanel\Tests\Fixtures\Car;
use JTD\AdminPanel\Tests\Fixtures\CarOwner;
use JTD\AdminPanel\Tests\Fixtures\CarOwnerResource;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * HasOneThrough Field Integration Test.
 *
 * Tests the complete integration between PHP HasOneThrough field class,
 * API endpoints, and frontend functionality with 100% Nova v5 compatibility.
 */
class HasOneThroughFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with cars and car owners
        $user1 = User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);

        // Create cars for users
        $car1 = Car::factory()->create(['id' => 1, 'user_id' => 1, 'make' => 'Toyota', 'model' => 'Camry', 'vin' => 'VIN123']);
        $car2 = Car::factory()->create(['id' => 2, 'user_id' => 2, 'make' => 'Honda', 'model' => 'Civic', 'vin' => 'VIN456']);
        // User 3 has no car

        // Create car owners
        CarOwner::factory()->create(['id' => 1, 'car_id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'license_number' => 'DL123']);
        CarOwner::factory()->create(['id' => 2, 'car_id' => 2, 'name' => 'Bob Brown', 'email' => 'bob.brown@example.com', 'license_number' => 'DL456']);
        // Car 1 has owner, Car 2 has owner, User 3 has no car so no owner
    }

    /** @test */
    public function it_creates_has_one_through_field_with_nova_syntax(): void
    {
        $field = HasOneThrough::make('Car Owner');

        $this->assertEquals('Car Owner', $field->name);
        $this->assertEquals('car_owner', $field->attribute);
        $this->assertEquals('car_owner', $field->relationshipName);
    }

    /** @test */
    public function it_creates_has_one_through_field_with_custom_resource(): void
    {
        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);

        $this->assertEquals('Car Owner', $field->name);
        $this->assertEquals('carOwner', $field->attribute);
        $this->assertEquals('carOwner', $field->relationshipName);
        $this->assertEquals(CarOwnerResource::class, $field->resourceClass);
    }

    /** @test */
    public function it_supports_all_nova_configuration_methods(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->resource(CarOwnerResource::class)
            ->relationship('carOwner')
            ->through('App\\Models\\Car')
            ->firstKey('user_id')
            ->secondKey('car_id')
            ->localKey('id')
            ->secondLocalKey('id');

        $meta = $field->meta();

        $this->assertEquals(CarOwnerResource::class, $meta['resourceClass']);
        $this->assertEquals('carOwner', $meta['relationshipName']);
        $this->assertEquals('App\\Models\\Car', $meta['through']);
        $this->assertEquals('user_id', $meta['firstKey']);
        $this->assertEquals('car_id', $meta['secondKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('id', $meta['secondLocalKey']);
    }

    /** @test */
    public function it_resolves_has_one_through_relationship_correctly(): void
    {
        $user = User::with(['car.carOwner'])->find(1);

        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertArrayHasKey('id', $field->value);
        $this->assertArrayHasKey('title', $field->value);
        $this->assertArrayHasKey('resource_class', $field->value);
        $this->assertArrayHasKey('exists', $field->value);
        $this->assertArrayHasKey('through', $field->value);

        $this->assertEquals(1, $field->value['id']); // CarOwner ID
        $this->assertEquals('Alice Johnson', $field->value['title']);
        $this->assertEquals(CarOwnerResource::class, $field->value['resource_class']);
        $this->assertTrue($field->value['exists']);
    }

    /** @test */
    public function it_resolves_empty_relationship_correctly(): void
    {
        $user = User::find(3); // User without car/owner

        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $field->resolve($user);

        $this->assertIsArray($field->value);
        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertEquals(CarOwnerResource::class, $field->value['resource_class']);
        $this->assertFalse($field->value['exists']);
    }

    /** @test */
    public function it_gets_related_model_correctly(): void
    {
        $user = User::with(['car.carOwner'])->find(1);

        $field = HasOneThrough::make('Car Owner', 'carOwner');
        $relatedModel = $field->getRelatedModel($user);

        $this->assertInstanceOf(CarOwner::class, $relatedModel);
        $this->assertEquals('Alice Johnson', $relatedModel->name);
        $this->assertEquals('alice@example.com', $relatedModel->email);
    }

    /** @test */
    public function it_gets_null_for_missing_relationship(): void
    {
        $user = User::find(3); // User without car/owner

        $field = HasOneThrough::make('Car Owner', 'carOwner');
        $relatedModel = $field->getRelatedModel($user);

        $this->assertNull($relatedModel);
    }

    /** @test */
    public function it_handles_fill_with_custom_callback(): void
    {
        $request = new Request(['carOwner' => 'test']);
        $user = User::find(1);
        $callbackCalled = false;

        $field = HasOneThrough::make('Car Owner', 'carOwner');
        $field->fillCallback = function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $this->assertEquals('carOwner', $attribute);
            $this->assertInstanceOf(User::class, $model);
            $this->assertInstanceOf(Request::class, $request);
        };

        $field->fill($request, $user);

        $this->assertTrue($callbackCalled);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->resource(CarOwnerResource::class)
            ->relationship('carOwner')
            ->through('App\\Models\\Car')
            ->firstKey('user_id')
            ->secondKey('car_id')
            ->localKey('id')
            ->secondLocalKey('id');

        $meta = $field->meta();

        // Check all required meta fields
        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('through', $meta);
        $this->assertArrayHasKey('firstKey', $meta);
        $this->assertArrayHasKey('secondKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('secondLocalKey', $meta);

        // Check values
        $this->assertEquals(CarOwnerResource::class, $meta['resourceClass']);
        $this->assertEquals('carOwner', $meta['relationshipName']);
        $this->assertEquals('App\\Models\\Car', $meta['through']);
        $this->assertEquals('user_id', $meta['firstKey']);
        $this->assertEquals('car_id', $meta['secondKey']);
        $this->assertEquals('id', $meta['localKey']);
        $this->assertEquals('id', $meta['secondLocalKey']);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->resource(CarOwnerResource::class)
            ->through('App\\Models\\Car')
            ->help('View the car owner through the car relationship');

        $json = $field->jsonSerialize();

        $this->assertEquals('Car Owner', $json['name']);
        $this->assertEquals('car_owner', $json['attribute']);
        $this->assertEquals('HasOneThroughField', $json['component']);
        $this->assertEquals(CarOwnerResource::class, $json['resourceClass']);
        $this->assertEquals('App\\Models\\Car', $json['through']);
        $this->assertEquals('View the car owner through the car relationship', $json['helpText']);
    }

    /** @test */
    public function it_guesses_resource_class_correctly(): void
    {
        $field = HasOneThrough::make('Car Owner', 'car_owner');

        $this->assertEquals('App\\AdminPanel\\Resources\\CarOwner', $field->resourceClass);
    }

    /** @test */
    public function it_is_only_shown_on_detail_by_default(): void
    {
        $field = HasOneThrough::make('Car Owner');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_can_be_configured_for_different_views(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->showOnIndex()
            ->showOnCreating()
            ->showOnUpdating();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_works_with_complex_resource_relationships(): void
    {
        // Test with a user that has a car and car owner
        $user = User::with(['car.carOwner'])->find(1);

        // Test regular HasOneThrough
        $carOwnerField = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $carOwnerField->resolve($user);

        $this->assertEquals(1, $carOwnerField->value['id']);
        $this->assertEquals('Alice Johnson', $carOwnerField->value['title']);
        $this->assertEquals(CarOwnerResource::class, $carOwnerField->value['resource_class']);
        $this->assertTrue($carOwnerField->value['exists']);

        // Test with different user
        $user2 = User::with(['car.carOwner'])->find(2);
        $carOwnerField2 = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $carOwnerField2->resolve($user2);

        $this->assertEquals(2, $carOwnerField2->value['id']);
        $this->assertEquals('Bob Brown', $carOwnerField2->value['title']);
        $this->assertTrue($carOwnerField2->value['exists']);
    }

    /** @test */
    public function it_handles_missing_intermediate_model(): void
    {
        // User 3 has no car, so no car owner through relationship
        $user = User::find(3);

        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $field->resolve($user);

        $this->assertNull($field->value['id']);
        $this->assertNull($field->value['title']);
        $this->assertFalse($field->value['exists']);
    }

    /** @test */
    public function it_supports_custom_through_configuration(): void
    {
        $field = HasOneThrough::make('Car Owner')
            ->through('App\\Models\\Vehicle')
            ->firstKey('owner_id')
            ->secondKey('vehicle_id')
            ->localKey('uuid')
            ->secondLocalKey('uuid');

        $meta = $field->meta();

        $this->assertEquals('App\\Models\\Vehicle', $meta['through']);
        $this->assertEquals('owner_id', $meta['firstKey']);
        $this->assertEquals('vehicle_id', $meta['secondKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertEquals('uuid', $meta['secondLocalKey']);
    }

    /** @test */
    public function it_works_with_resource_title_method(): void
    {
        $user = User::with(['car.carOwner'])->find(1);

        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $field->resolve($user);

        // The title should come from the CarOwnerResource's title() method
        $this->assertEquals('Alice Johnson', $field->value['title']);
    }

    /** @test */
    public function it_handles_soft_deleted_relationships(): void
    {
        // Create a car owner and then soft delete it
        $user = User::with(['car.carOwner'])->find(1);
        $carOwner = $user->carOwner;
        $carOwner->delete(); // Soft delete

        // Refresh the user to get updated relationships
        $user->refresh();

        $field = HasOneThrough::make('Car Owner', 'carOwner', CarOwnerResource::class);
        $field->resolve($user);

        // Should not find the soft-deleted car owner
        $this->assertNull($field->value['id']);
        $this->assertFalse($field->value['exists']);
    }
}

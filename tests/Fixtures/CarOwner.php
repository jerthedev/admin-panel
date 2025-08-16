<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CarOwner Model Fixture for Testing.
 *
 * Used for testing HasOneThrough relationships.
 * Represents the final model in User -> Car -> CarOwner relationship.
 */
class CarOwner extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'car_owners';

    protected $fillable = [
        'car_id',
        'name',
        'email',
        'phone',
        'license_number',
    ];

    /**
     * Get the car that belongs to this owner.
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the user through the car.
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Car::class, 'id', 'id', 'car_id', 'user_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CarOwnerFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new CarOwnerFactory;
    }
}

/**
 * CarOwner Factory for testing.
 */
class CarOwnerFactory
{
    protected $model = CarOwner::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'car_id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'license_number' => 'DL123456',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $carOwner = new CarOwner;
        $carOwner->fill($attributes);
        $carOwner->save();

        return $carOwner;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'car_id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'license_number' => 'DL123456',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $carOwner = new CarOwner;
        $carOwner->fill($attributes);

        return $carOwner;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

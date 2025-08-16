<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Car Model Fixture for Testing.
 *
 * Used for testing HasOneThrough relationships.
 * Represents the intermediate model in User -> Car -> CarOwner relationship.
 */
class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cars';

    protected $fillable = [
        'user_id',
        'make',
        'model',
        'year',
        'vin',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Get the user that owns the car.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the car owner.
     */
    public function carOwner()
    {
        return $this->hasOne(CarOwner::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CarFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new CarFactory;
    }
}

/**
 * Car Factory for testing.
 */
class CarFactory
{
    protected $model = Car::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'user_id' => 1,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vin' => 'ABC123456789',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $car = new Car;
        $car->fill($attributes);
        $car->save();

        return $car;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'user_id' => 1,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vin' => 'ABC123456789',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $car = new Car;
        $car->fill($attributes);

        return $car;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

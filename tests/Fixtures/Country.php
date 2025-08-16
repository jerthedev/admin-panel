<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Country Model Fixture for Testing.
 *
 * Used for testing HasManyThrough relationships.
 * Represents the parent model in Country -> User -> Post relationship.
 */
class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'code',
        'continent',
    ];

    /**
     * Get the users in this country.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all posts through users in this country.
     */
    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CountryFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new CountryFactory;
    }
}

/**
 * Country Factory for testing.
 */
class CountryFactory
{
    protected $model = Country::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'name' => 'United States',
            'code' => 'US',
            'continent' => 'North America',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $country = new Country;
        $country->fill($attributes);
        $country->save();

        return $country;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'name' => 'United States',
            'code' => 'US',
            'continent' => 'North America',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $country = new Country;
        $country->fill($attributes);

        return $country;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Address Test Fixture Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $street
 * @property string $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Address extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'zip',
        'country',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AddressFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new AddressFactory();
    }
}

/**
 * Address Factory for testing
 */
class AddressFactory
{
    protected $model = Address::class;
    protected $attributes = [];

    public static function new()
    {
        return new static();
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'user_id' => 1,
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345',
            'country' => 'USA',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $address = new Address();
        $address->fill($attributes);
        $address->save();

        return $address;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'user_id' => 1,
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345',
            'country' => 'USA',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $address = new Address();
        $address->fill($attributes);

        return $address;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }
}

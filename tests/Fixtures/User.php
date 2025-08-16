<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use JTD\AdminPanel\Tests\Factories\UserFactory;

/**
 * Test User Model
 *
 * User model for testing admin panel functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
        'country_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Get the user's car.
     */
    public function car()
    {
        return $this->hasOne(Car::class);
    }

    /**
     * Get the car owner through the car.
     */
    public function carOwner()
    {
        return $this->hasOneThrough(CarOwner::class, Car::class);
    }

    /**
     * Get the country this user belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the roles assigned to this user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get all of the tags for the user.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['notes', 'priority'])
            ->withTimestamps();
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}

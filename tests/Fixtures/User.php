<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use JTD\AdminPanel\Tests\Factories\UserFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Test User Model
 *
 * User model for testing admin panel functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
        'country_id',
        'avatar',      // For Avatar field E2E tests
        'theme_song',  // For Audio field E2E tests
        'permissions', // For BooleanGroup field E2E tests
        'features',    // For BooleanGroup field E2E tests
        'code',        // For Code field E2E tests
        'config',      // For Code field E2E tests (JSON)
        'color',       // For Color field tests
        'skills',      // For MultiSelect field tests
        'tags',        // For MultiSelect field tests
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array', // For BooleanGroup field E2E tests
        'features' => 'array',    // For BooleanGroup field E2E tests
        'config' => 'array',      // For Code field E2E tests (JSON)
        'skills' => 'array',      // For MultiSelect field tests
        'tags' => 'array',        // For MultiSelect field tests
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

    /**
     * Get the media model class name.
     */
    public function getMediaModel(): string
    {
        return config('media-library.media_model', Media::class);
    }

    /**
     * Register media collections for the user.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
    }

    /**
     * Register media conversions for the user.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(64)
            ->height(64)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $this->addMediaConversion('large')
            ->width(400)
            ->height(400)
            ->sharpen(10);
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Role Model Fixture for Testing.
 *
 * Used for testing BelongsToMany relationships.
 * Represents roles that can be assigned to users through a pivot table.
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that have this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoleFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new RoleFactory;
    }
}

/**
 * Role Factory for testing.
 */
class RoleFactory
{
    protected $model = Role::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator role',
            'is_active' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $role = new Role;
        $role->fill($attributes);
        $role->save();

        return $role;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator role',
            'is_active' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $role = new Role;
        $role->fill($attributes);

        return $role;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

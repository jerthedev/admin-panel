<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tag Model Fixture for Testing.
 *
 * Used for testing MorphToMany relationships.
 * Represents tags that can be attached to various models polymorphically.
 */
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    /**
     * Get all of the users that are assigned this tag.
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'taggable');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TagFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new TagFactory;
    }
}

/**
 * Tag Factory for testing.
 */
class TagFactory
{
    protected $model = Tag::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'description' => 'This is a test tag.',
            'color' => '#3B82F6',
            'is_active' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $tag = new Tag;
        $tag->fill($attributes);
        $tag->save();

        return $tag;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'description' => 'This is a test tag.',
            'color' => '#3B82F6',
            'is_active' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $tag = new Tag;
        $tag->fill($attributes);

        return $tag;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

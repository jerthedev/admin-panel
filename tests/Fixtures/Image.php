<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Image Model Fixture for Testing.
 *
 * Used for testing MorphOne relationships.
 * Represents images that can be attached to various models polymorphically.
 */
class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'images';

    protected $fillable = [
        'filename',
        'path',
        'alt_text',
        'size',
        'mime_type',
        'imageable_type',
        'imageable_id',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the parent imageable model (post, user, etc.).
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ImageFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new ImageFactory;
    }
}

/**
 * Image Factory for testing.
 */
class ImageFactory
{
    protected $model = Image::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'filename' => 'test-image.jpg',
            'path' => '/images/test-image.jpg',
            'alt_text' => 'Test image',
            'size' => 1024000, // 1MB
            'mime_type' => 'image/jpeg',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $image = new Image;
        $image->fill($attributes);
        $image->save();

        return $image;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'filename' => 'test-image.jpg',
            'path' => '/images/test-image.jpg',
            'alt_text' => 'Test image',
            'size' => 1024000, // 1MB
            'mime_type' => 'image/jpeg',
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $image = new Image;
        $image->fill($attributes);

        return $image;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

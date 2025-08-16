<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use JTD\AdminPanel\Tests\Factories\PostFactory;

/**
 * Test Post Model
 *
 * Post model for testing admin panel functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'status',
        'is_published',
        'is_featured',
        'user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post's image (polymorphic).
     */
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    /**
     * Get the latest image (morph one of many).
     */
    public function latestImage()
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    /**
     * Get the post's comments (polymorphic).
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get all of the tags for the post.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['notes', 'priority'])
            ->withTimestamps();
    }

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}

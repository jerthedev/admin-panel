<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comment Model Fixture for Testing.
 *
 * Used for testing MorphMany relationships.
 * Represents comments that can be attached to various models polymorphically.
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'content',
        'author_name',
        'author_email',
        'is_approved',
        'commentable_type',
        'commentable_id',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * Get the parent commentable model (post, user, etc.).
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CommentFactory::new();
    }

    /**
     * Simple factory method for testing.
     */
    public static function factory()
    {
        return new CommentFactory;
    }
}

/**
 * Comment Factory for testing.
 */
class CommentFactory
{
    protected $model = Comment::class;

    protected $attributes = [];

    public static function new()
    {
        return new static;
    }

    public function create(array $attributes = [])
    {
        $defaults = [
            'content' => 'This is a test comment.',
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'is_approved' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $comment = new Comment;
        $comment->fill($attributes);
        $comment->save();

        return $comment;
    }

    public function make(array $attributes = [])
    {
        $defaults = [
            'content' => 'This is a test comment.',
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'is_approved' => true,
        ];

        $attributes = array_merge($defaults, $this->attributes, $attributes);

        $comment = new Comment;
        $comment->fill($attributes);

        return $comment;
    }

    public function state(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }
}

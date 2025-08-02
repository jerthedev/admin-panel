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

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}

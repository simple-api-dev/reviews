<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Comment;

/**
 * @method static where(string $string, mixed $integration_id)
 * @method static make(array $all)
 * @method static find($id)
 */
class Review extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'rating', 'title', 'body', 'bad', 'spam', 
        'helpful_counter', 'unhelpful_counter', 'related_slug', 
        'author', 'author_email', 'author_slug', 'meta', 'datetime'
    ];

    protected $hidden = [
        'id', 'deleted_at', 'updated_at', 'created_at', 'integration_id'
    ];

    public function getMetaAttribute($value){
        return $value ? json_decode($value) : null;
     }

    //determines if review is helpful or not
    public function isHelpful()
    {
        return $this->helpful_counter >= $this->unhelpful_counter;
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class, 'review_slug', 'slug');
    }
}

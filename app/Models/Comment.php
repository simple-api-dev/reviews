<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static where(string $string, mixed $integration_id)
 * @method static make(array $all)
 * @method static find($id)
 */
class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'review_slug', 'body', 'author', 'author_email', 
        'author_slug', 'meta', 'datetime'
    ];

    protected $hidden = [
        'review_slug', 'deleted_at', 'updated_at', 'created_at', 'integration_id'
    ];

    public function getMetaAttribute($value){
        return $value ? json_decode($value) : null;
    }

    public function review()
    {
        return $this->belongsTo(App\Models\Review::class, 'slug', 'review_slug');
    }
}

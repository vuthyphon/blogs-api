<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'body',
        'user_id',
        'category_id',
        'image',
        'slug',
    ];

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function tags() {
        return $this->belongsToMany(Tag::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

}

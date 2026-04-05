<?php

namespace Modules\CRM\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $connection = 'crm';

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'header_image',
        'thumbnail',
        'content',
        'author_id',
        'author_name',
        'author_profile_image',
        'author_designation',
        'read_time',
        'status',
        'post_type',
        'press_release_link',
        'publish_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'publish_date' => 'date',
    ];

    public function scopeActiveOnly($query)
    {
        $query->where('status', '=', 'publish');
    }

    public function author()
    {
        return $this->setConnection('pgsql')->hasOne(User::class, 'id', 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Post\Tag::class, 'post_tag_map')
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Post\Category::class, 'post_category_map')
            ->withTimestamps();
    }
}

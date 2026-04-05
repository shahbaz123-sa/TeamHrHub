<?php

namespace Modules\CRM\Models\Post;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'crm';

    protected $table = 'post_categories';

    protected $fillable = [
        'title',
        'slug',
        'is_active',
    ];
}

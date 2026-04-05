<?php

namespace Modules\CRM\Models\Post;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $connection = 'crm';

    protected $table = 'post_tags';

    protected $fillable = [
        'title',
        'slug',
        'is_active',
    ];
}

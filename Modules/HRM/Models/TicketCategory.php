<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    protected $fillable = ['name', 'description', 'status'];

    protected $casts = ['status' => 'boolean'];
}

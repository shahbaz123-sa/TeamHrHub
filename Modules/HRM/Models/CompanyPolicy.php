<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'display_order',
        'title',
        'description',
        'attachment_path',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

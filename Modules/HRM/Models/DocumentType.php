<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'is_default', 'order'];

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\DocumentTypeFactory::new();
    }
}

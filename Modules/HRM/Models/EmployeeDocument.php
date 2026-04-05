<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_type_id', 
        'file_path'
    ];

    protected $appends = ['file_url'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::disk('s3')->url($this->file_path) : null;
    }
}

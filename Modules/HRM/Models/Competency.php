<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\CompetencyFactory::new();
    }
}

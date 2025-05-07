<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'start_year',
        'end_year',
    ];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function documentDeadlines()
    {
        return $this->hasMany(DocumentDeadline::class);
    }
}

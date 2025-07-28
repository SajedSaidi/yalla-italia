<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    protected $fillable = [
        'name',
        'code'
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_qualifications');
    }
}

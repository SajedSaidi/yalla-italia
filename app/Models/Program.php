<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'university_id',
        'major_id',
        'academic_year_id',
        'application_deadline',
        'application_fee',
        'enrollment_fee',
        'description',
    ];

    public function getCompositeTitleAttribute(): string
    {
        $uni   = $this->university->name;
        $major = $this->major->name;
        $year  = $this->academicYear->name;

        return "{$uni} – {$major} – {$year}";
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'applications')
            ->withPivot(['status', 'notes'])
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Student extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    const QUALIFICATIONS = [
        'bachelor_degree' => 'Bachelor degree',
        'masters_degree' => 'Masters degree',
        'lebanese_baccalaureate' => 'Lebanese baccalaureate',
        'technical_baccalaureate' => 'Technical baccalaureate',
        'high_school_diploma' => 'High school diploma',
        'freshmen' => 'Freshmen',
    ];

    protected $fillable = [
        'user_id',
        'phone',
        'date_of_birth',
        'place_of_birth',
        'qualifications',
        'address',
        'nationality_id'
    ];

    public static function getQualificationOptions(): array
    {
        return self::QUALIFICATIONS;
    }

    public function getQualificationDisplayAttribute(): string
    {
        return self::QUALIFICATIONS[$this->qualifications] ?? $this->qualifications;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'applications')
            ->withPivot(['status', 'notes'])
            ->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }
}

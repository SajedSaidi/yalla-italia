<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Student extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'date_of_birth',
        'place_of_birth',
        'address',
        'nationality_id'
    ];

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

    public function qualifications()
    {
        return $this->belongsToMany(Qualification::class, 'student_qualifications');
    }

    // New relationship for language certificates
    public function languageCertificates()
    {
        return $this->belongsToMany(LanguageCertificate::class, 'student_language_certificates');
    }
}

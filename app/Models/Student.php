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

    protected static function booted(): void
    {
        // documents.student_id is a restricting foreign key; deleting each document
        // individually also removes its stored file. Applications and pivots cascade in the DB.
        static::deleting(function (Student $student) {
            $student->documents()->get()->each->delete();
        });
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

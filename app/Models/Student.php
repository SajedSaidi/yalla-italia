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
        'qualifications',
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
}

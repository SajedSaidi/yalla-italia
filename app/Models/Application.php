<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Application extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'student_id',
        'program_id',
        'payment_status',
        'status',
        'notes',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}

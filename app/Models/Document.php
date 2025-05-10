<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Document extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'student_id',
        'document_type_id',
        'name',
        'document_url',
        'notes',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

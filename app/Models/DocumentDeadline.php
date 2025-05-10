<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DocumentDeadline extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $table = "document_deadlines";


    protected $fillable = [
        'academic_year_id',
        'document_type_id',
        'deadline',
        'notes',
        'university_id',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}

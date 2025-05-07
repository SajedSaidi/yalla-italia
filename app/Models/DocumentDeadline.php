<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentDeadline extends Model
{
    use SoftDeletes;
    protected $table = "document_deadlines";


    protected $fillable = [
        'academic_year_id',
        'document_type_id',
        'deadline',
        'notes',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'education_level',
    ];

    // Relationships
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

    // Enhanced composite title attribute
    public function getFormattedTitleAttribute()
    {
        // Format education level nicely
        $educationLevelLabel = match ($this->education_level) {
            'single_cycle' => 'Single Cycle',
            'bachelor' => 'Bachelor',
            'master' => 'Master',
            'phd' => 'PhD',
            default => $this->education_level,
        };

        // Build parts array and filter out empty elements
        $parts = array_filter([
            $this->documentType?->name,
            $this->university?->name,
            $educationLevelLabel,
        ]);

        // Join parts with a separator
        return implode(' | ', $parts);
    }

    // Legacy title attribute for backward compatibility
    public function getTitleAttribute()
    {
        return $this->formatted_title;
    }
}

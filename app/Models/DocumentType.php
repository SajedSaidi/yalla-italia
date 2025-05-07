<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (DocumentType $type): void {
            // Only regenerate slug if name was changed or slug is empty
            if ($type->isDirty('name') || !$type->slug) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function academicYears()
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_year_document_type')
            ->withPivot(['deadline', 'notes'])
            ->withTimestamps();
    }

    public function documentDeadlines()
    {
        return $this->hasMany(DocumentDeadline::class);
    }
}

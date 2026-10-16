<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class Document extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Files are stored under a random, unguessable name; the readable
     * name below is only used when someone downloads the file.
     */
    public const DISK = 'public';

    protected $fillable = [
        'student_id',
        'document_type_id',
        'name',
        'document_url',
        'notes',
        'status',
    ];

    protected static function booted(): void
    {
        static::updated(function (Document $document) {
            $previous = $document->getOriginal('document_url');

            if ($document->wasChanged('document_url') && filled($previous)) {
                Storage::disk(self::DISK)->delete($previous);
            }
        });

        static::deleted(function (Document $document) {
            if (filled($document->document_url)) {
                Storage::disk(self::DISK)->delete($document->document_url);
            }
        });
    }

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

    /**
     * e.g. "Mario Rossi - Passport - Passport scan.pdf"
     */
    public function getDownloadFileNameAttribute(): string
    {
        $parts = collect([
            $this->student?->user?->name,
            $this->documentType?->name,
            $this->name,
        ])
            ->map(fn (?string $part) => self::sanitizeFileNamePart($part))
            ->filter()
            ->unique(fn (string $part) => Str::lower($part))
            ->implode(' - ');

        $extension = Str::lower(pathinfo((string) $this->document_url, PATHINFO_EXTENSION));

        $base = Str::limit($parts !== '' ? $parts : 'document-' . $this->getKey(), 150, '');

        return $extension !== '' ? "{$base}.{$extension}" : $base;
    }

    public static function sanitizeFileNamePart(?string $value): string
    {
        // Strip characters that are invalid in Windows/macOS file names, then collapse whitespace.
        $value = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', (string) $value);

        return trim(preg_replace('/\s+/u', ' ', $value), " .-");
    }
}

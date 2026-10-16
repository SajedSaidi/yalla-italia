<?php

namespace App\Filament\Forms;

use App\Models\Document;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Upload field shared by the student "Documents" page and the manager's
 * student documents tab. Files keep their random storage name; the file
 * picker's download button saves them under the document's readable name.
 */
class DocumentFileUpload
{
    public const ACCEPTED_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public static function make(string $name = 'document_url'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Upload File')
            ->helperText('PDF, image (JPG, PNG, WEBP) or Word document, up to 10 MB.')
            ->directory('documents')
            ->disk(Document::DISK)
            ->visibility('public')
            ->acceptedFileTypes(self::ACCEPTED_TYPES)
            ->maxSize(10240)
            ->required()
            ->openable()
            ->downloadable()
            ->previewable()
            ->getUploadedFileUsing(function (FileUpload $component, string $file, ?Document $record): ?array {
                /** @var Filesystem $storage */
                $storage = $component->getDisk();

                if (! $storage->exists($file)) {
                    return null;
                }

                $record?->loadMissing('student.user', 'documentType');

                return [
                    'name' => $record?->document_url === $file ? $record->download_file_name : basename($file),
                    'size' => $storage->size($file),
                    'type' => $storage->mimeType($file),
                    'url' => $storage->url($file),
                ];
            });
    }
}

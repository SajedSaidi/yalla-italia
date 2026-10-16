<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Serves uploaded documents under a readable file name
 * ("Student Name - Document Type - Document Name.pdf") while the files
 * themselves stay stored under random names.
 */
class DocumentDownloadController extends Controller
{
    public function show(Document $document): StreamedResponse
    {
        Gate::authorize('download-document', $document);

        $disk = Storage::disk(Document::DISK);

        abort_unless(filled($document->document_url) && $disk->exists($document->document_url), 404, 'File not found.');

        $document->loadMissing('student.user', 'documentType');

        return $disk->download($document->document_url, $document->download_file_name);
    }

    public function student(Student $student): BinaryFileResponse
    {
        Gate::authorize('download-student-documents');

        $disk = Storage::disk(Document::DISK);

        $documents = $student->documents()
            ->with('student.user', 'documentType')
            ->get()
            ->filter(fn (Document $document) => filled($document->document_url) && $disk->exists($document->document_url));

        abort_if($documents->isEmpty(), 404, 'This student has no uploaded files.');

        $zipPath = tempnam(sys_get_temp_dir(), 'docs');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        $usedNames = [];

        foreach ($documents as $document) {
            $name = $document->download_file_name;

            // Never overwrite an entry if two documents end up with the same name.
            if (isset($usedNames[strtolower($name)])) {
                $name = pathinfo($name, PATHINFO_FILENAME) . " ({$document->id})." . pathinfo($name, PATHINFO_EXTENSION);
            }

            $usedNames[strtolower($name)] = true;
            $zip->addFile($disk->path($document->document_url), $name);
        }

        $zip->close();

        $studentName = Document::sanitizeFileNamePart($student->user?->name) ?: "student-{$student->id}";

        return response()
            ->download($zipPath, "{$studentName} - Documents.zip", ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}

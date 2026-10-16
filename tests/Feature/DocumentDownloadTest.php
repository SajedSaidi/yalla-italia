<?php

namespace Tests\Feature;

use App\Filament\Resources\StudentResource\Pages\ViewStudent;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->student = $this->makeStudent(['name' => 'Mario Rossi']);
        $this->document = $this->uploadDocument($this->student, 'Passport', 'Passport scan', 'pdf');
    }

    private function uploadDocument(Student $student, string $type, string $name, string $extension): Document
    {
        $path = UploadedFile::fake()->create("whatever.{$extension}", 20)->store('documents', 'public');

        return Document::create([
            'student_id' => $student->id,
            'document_type_id' => DocumentType::firstOrCreate(['name' => $type])->id,
            'name' => $name,
            'document_url' => $path,
        ]);
    }

    public function test_files_keep_a_random_storage_name(): void
    {
        $this->assertMatchesRegularExpression('#^documents/[A-Za-z0-9]{20,}\.pdf$#', $this->document->document_url);
    }

    public function test_manager_downloads_with_a_readable_file_name(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('documents.download', $this->document))
            ->assertOk()
            ->assertDownload('Mario Rossi - Passport - Passport scan.pdf');
    }

    public function test_admin_can_download_too(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('documents.download', $this->document))
            ->assertDownload('Mario Rossi - Passport - Passport scan.pdf');
    }

    public function test_student_can_download_only_their_own_documents(): void
    {
        $this->actingAs($this->student->user)
            ->get(route('documents.download', $this->document))
            ->assertDownload();

        $this->actingAs($this->makeStudent()->user)
            ->get(route('documents.download', $this->document))
            ->assertForbidden();
    }

    public function test_guests_and_unapproved_students_are_kept_out(): void
    {
        $this->get(route('documents.download', $this->document))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->student->user->update(['is_approved' => false]);

        $this->actingAs($this->student->user)
            ->get(route('documents.download', $this->document))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::disk('public')->delete($this->document->document_url);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('documents.download', $this->document))
            ->assertNotFound();
    }

    public function test_unsafe_characters_are_removed_from_the_name(): void
    {
        $this->student->user->update(['name' => 'Ana: "Lou"/Smith']);
        $document = $this->uploadDocument($this->student, 'Bachelor <Diploma>', 'passport', 'png');

        $this->assertSame('Ana Lou Smith - Bachelor Diploma - passport.png', $document->fresh()->download_file_name);
    }

    public function test_duplicate_name_parts_are_not_repeated(): void
    {
        $document = $this->uploadDocument($this->student, 'Transcript', 'transcript', 'pdf');

        $this->assertSame('Mario Rossi - Transcript.pdf', $document->fresh()->download_file_name);
    }

    public function test_manager_downloads_all_documents_of_a_student_as_a_zip(): void
    {
        $this->uploadDocument($this->student, 'Transcript', 'High school transcript', 'pdf');

        $response = $this->actingAs(User::factory()->manager()->create())
            ->get(route('students.documents.download', $this->student))
            ->assertOk()
            ->assertDownload('Mario Rossi - Documents.zip');

        $zip = new ZipArchive();
        $zip->open($response->baseResponse->getFile()->getPathname());
        $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();
        $zip->close();

        $this->assertSame([
            'Mario Rossi - Passport - Passport scan.pdf',
            'Mario Rossi - Transcript - High school transcript.pdf',
        ], $names);
    }

    public function test_students_cannot_download_the_zip(): void
    {
        $this->actingAs($this->student->user)
            ->get(route('students.documents.download', $this->student))
            ->assertForbidden();
    }

    public function test_relation_manager_shows_download_actions(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $this->student,
            'pageClass' => ViewStudent::class,
        ])
            ->assertTableActionVisible('download', $this->document)
            ->assertTableActionHasUrl('download', route('documents.download', $this->document), $this->document)
            ->assertTableActionVisible('downloadAll');
    }

    public function test_deleting_or_replacing_a_document_removes_the_old_file(): void
    {
        $old = $this->document->document_url;
        $new = UploadedFile::fake()->create('new.pdf', 5)->store('documents', 'public');

        $this->document->update(['document_url' => $new]);
        Storage::disk('public')->assertMissing($old);

        $this->document->delete();
        Storage::disk('public')->assertMissing($new);
    }
}

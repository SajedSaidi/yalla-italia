<?php

namespace Tests\Feature;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\CreateStudent;
use App\Filament\Resources\StudentResource\Pages\EditStudent;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Filament\Resources\StudentResource\Pages\ViewStudent;
use App\Filament\Resources\UserResource;
use App\Mail\UserApproved;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->manager = User::factory()->manager()->create();
        $this->actingAs($this->manager);
    }

    public function test_manager_uses_the_students_page_instead_of_users(): void
    {
        $this->get(StudentResource::getUrl('index'))->assertOk()->assertSee('Students');
        $this->get(UserResource::getUrl('index'))->assertForbidden();

        $this->get('/admin')->assertOk()->assertDontSee(UserResource::getUrl('index'));
    }

    public function test_admin_still_has_the_users_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(UserResource::getUrl('index'))->assertOk();
        $this->get(StudentResource::getUrl('index'))->assertOk();
    }

    public function test_students_cannot_open_the_students_page(): void
    {
        $this->actingAs($this->makeStudent()->user);

        $this->get(StudentResource::getUrl('index'))->assertForbidden();
    }

    public function test_manager_creates_the_account_and_profile_in_one_form(): void
    {
        Livewire::test(CreateStudent::class)
            ->fillForm([
                'user.name' => 'Giulia Bianchi',
                'user.email' => 'giulia@example.com',
                'user.password' => 'secret-password',
                'user.is_approved' => true,
                'phone' => '+39 111',
                'date_of_birth' => '2002-02-02',
                'place_of_birth' => 'Milan',
                'address' => 'Via Milano 3',
                'nationality_id' => $this->nationality()->id,
                'qualifications' => [$this->qualification()->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'giulia@example.com')->firstOrFail();

        $this->assertSame('student', $user->role);
        $this->assertTrue($user->is_approved);
        $this->assertSame($this->manager->id, $user->approved_by);
        $this->assertNotNull($user->approved_at);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame('Milan', $user->student->place_of_birth);
        $this->assertSame([$this->qualification()->id], $user->student->qualifications->pluck('id')->all());
    }

    public function test_create_validates_account_fields(): void
    {
        $existing = $this->makeStudent();

        Livewire::test(CreateStudent::class)
            ->fillForm([
                'user.name' => '',
                'user.email' => $existing->user->email,
                'user.password' => 'short',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'user.name' => 'required',
                'user.email' => 'unique',
                'user.password' => 'min',
                'phone' => 'required',
            ]);

        $this->assertSame(1, Student::count());
    }

    public function test_manager_edits_account_and_profile_together_and_password_is_optional(): void
    {
        $student = $this->makeStudent(['name' => 'Old Name']);
        $oldHash = $student->user->password;

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->assertFormSet([
                'user.name' => 'Old Name',
                'user.email' => $student->user->email,
                'user.is_approved' => true,
            ])
            ->fillForm([
                'user.name' => 'New Name',
                'user.email' => 'changed@example.com',
                'user.password' => '',
                'phone' => '999',
                'qualifications' => [$this->qualification()->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $student->refresh();

        $this->assertSame('New Name', $student->user->name);
        $this->assertSame('changed@example.com', $student->user->email);
        $this->assertSame($oldHash, $student->user->password, 'Blank password must keep the old one.');
        $this->assertSame('999', $student->phone);

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->fillForm(['user.password' => 'brand-new-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('brand-new-password', $student->user->fresh()->password));
    }

    public function test_edit_keeps_the_email_unique(): void
    {
        $other = $this->makeStudent();
        $student = $this->makeStudent();

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->fillForm(['user.email' => $other->user->email])
            ->call('save')
            ->assertHasFormErrors(['user.email' => 'unique']);

        // Its own email is fine.
        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->fillForm(['user.email' => $student->user->email, 'qualifications' => [$this->qualification()->id]])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_approving_from_the_table_emails_the_student_once(): void
    {
        $student = $this->makeStudent(approved: false);

        Livewire::test(ListStudents::class)
            ->assertTableActionVisible('approve', $student)
            ->assertTableActionHidden('unapprove', $student)
            ->callTableAction('approve', $student);

        $user = $student->user->fresh();
        $this->assertTrue($user->is_approved);
        $this->assertSame($this->manager->id, $user->approved_by);

        Mail::assertSent(UserApproved::class, 1);
        Mail::assertSent(UserApproved::class, fn ($mail) => $mail->hasTo($user->email));

        Livewire::test(ListStudents::class)->callTableAction('unapprove', $student->fresh());

        $user->refresh();
        $this->assertFalse($user->is_approved);
        $this->assertNull($user->approved_at);
        $this->assertNull($user->approved_by);
        Mail::assertSent(UserApproved::class, 1);
    }

    public function test_approving_from_the_view_page_and_via_the_edit_toggle(): void
    {
        $first = $this->makeStudent(approved: false);
        $second = $this->makeStudent(approved: false);

        Livewire::test(ViewStudent::class, ['record' => $first->getRouteKey()])
            ->callAction('approve');

        Livewire::test(EditStudent::class, ['record' => $second->getRouteKey()])
            ->fillForm(['user.is_approved' => true, 'qualifications' => [$this->qualification()->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($first->user->fresh()->is_approved);
        $this->assertTrue($second->user->fresh()->is_approved);
        Mail::assertSent(UserApproved::class, 2);
    }

    public function test_bulk_approve(): void
    {
        $pending = collect([$this->makeStudent(approved: false), $this->makeStudent(approved: false)]);

        Livewire::test(ListStudents::class)->callTableBulkAction('approve_selected', $pending);

        $pending->each(fn (Student $s) => $this->assertTrue($s->user->fresh()->is_approved));
        Mail::assertSent(UserApproved::class, 2);
    }

    public function test_search_filter_tabs_and_sorting(): void
    {
        $old = $this->makeStudent(['name' => 'Anna Old', 'email' => 'anna@example.com']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();
        $new = $this->makeStudent(['name' => 'Zed New', 'email' => 'zed@example.com'], approved: false);

        Livewire::test(ListStudents::class)
            // Newest first by default
            ->assertCanSeeTableRecords([$new, $old], inOrder: true)
            ->sortTable('created_at', 'asc')
            ->assertCanSeeTableRecords([$old, $new], inOrder: true)
            ->sortTable('user.name', 'desc')
            ->assertCanSeeTableRecords([$new, $old], inOrder: true)
            ->sortTable('user.email', 'asc')
            ->assertCanSeeTableRecords([$old, $new], inOrder: true)
            ->sortTable('user.is_approved', 'asc')
            ->assertCanSeeTableRecords([$new, $old], inOrder: true);

        Livewire::test(ListStudents::class)
            ->searchTable('zed@example')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);

        Livewire::test(ListStudents::class)
            ->filterTable('approval', false)
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);

        Livewire::test(ListStudents::class)
            ->filterTable('registered_between', ['from' => now()->subDay()->toDateString()])
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);

        Livewire::test(ListStudents::class)
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);

        $this->assertSame('1', StudentResource::getNavigationBadge());
    }

    public function test_manager_deletes_a_student_with_account_documents_and_files(): void
    {
        Storage::fake('public');
        $student = $this->makeStudent();
        $path = UploadedFile::fake()->create('passport.pdf', 10, 'application/pdf')->store('documents', 'public');
        $document = Document::create([
            'student_id' => $student->id,
            'document_type_id' => DocumentType::create(['name' => 'Passport'])->id,
            'name' => 'Passport scan',
            'document_url' => $path,
        ]);

        Livewire::test(ListStudents::class)
            ->assertTableActionVisible(TableDeleteAction::class, $student)
            ->callTableAction(TableDeleteAction::class, $student);

        $this->assertModelMissing($student);
        $this->assertModelMissing($student->user);
        $this->assertModelMissing($document);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_manager_deletes_from_the_edit_page_and_in_bulk(): void
    {
        $one = $this->makeStudent();
        [$two, $three] = [$this->makeStudent(), $this->makeStudent()];

        Livewire::test(EditStudent::class, ['record' => $one->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertRedirect(StudentResource::getUrl('index'));

        Livewire::test(ListStudents::class)->callTableBulkAction(DeleteBulkAction::class, [$two, $three]);

        $this->assertSame(0, Student::count());
        $this->assertSame(0, User::where('role', 'student')->count());
    }
}

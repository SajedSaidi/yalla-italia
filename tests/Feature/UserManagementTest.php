<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentTypeResource\Pages\ListDocumentTypes;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Mail\UserApproved;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sort_users_by_every_sortable_column(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Mia Admin', 'email' => 'mia@example.com', 'created_at' => now()->subDays(5)]);
        $older = User::factory()->student(false)->create(['name' => 'Bob Older', 'email' => 'bob@example.com', 'created_at' => now()->subDays(30)]);
        $newer = User::factory()->manager()->create(['name' => 'Zoe Newer', 'email' => 'zoe@example.com', 'created_at' => now()]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            // Default: newest registered first
            ->assertCanSeeTableRecords([$newer, $admin, $older], inOrder: true)
            ->sortTable('created_at', 'asc')
            ->assertCanSeeTableRecords([$older, $admin, $newer], inOrder: true)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$older, $admin, $newer], inOrder: true)
            ->sortTable('email', 'desc')
            ->assertCanSeeTableRecords([$newer, $admin, $older], inOrder: true)
            ->sortTable('role')
            ->assertCanSeeTableRecords([$admin, $newer, $older], inOrder: true)
            ->sortTable('is_approved')
            ->assertCanSeeTableRecords([$older], inOrder: true)
            ->sortTable('approved_at', 'desc')
            ->assertSuccessful()
            ->sortTable('approvedBy.name')
            ->assertSuccessful()
            ->sortTable('updated_at')
            ->assertSuccessful();
    }

    public function test_admin_filters_users(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->student(false)->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('is_approved', false)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$admin])
            ->resetTableFilters()
            ->filterTable('role', 'admin')
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_admin_approval_via_edit_toggle_sends_one_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->student(false)->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_approved' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue($user->is_approved);
        $this->assertSame($admin->id, $user->approved_by);
        Mail::assertSent(UserApproved::class, 1);
    }

    public function test_admin_can_change_a_users_password_or_leave_it(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->manager()->create();
        $hash = $user->password;
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_admin_cannot_delete_their_own_account_even_in_bulk(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->manager()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden(DeleteAction::class, $admin)
            ->assertTableActionVisible(DeleteAction::class, $other)
            ->callTableBulkAction(DeleteBulkAction::class, [$admin, $other]);

        $this->assertModelExists($admin);
        $this->assertModelMissing($other);
    }

    public function test_user_policy_delete_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $student = User::factory()->student()->create();

        $this->assertTrue($manager->can('delete', $student));
        $this->assertFalse($manager->can('delete', $otherManager));
        $this->assertFalse($manager->can('delete', $admin));
        $this->assertFalse($manager->can('delete', $manager));

        $this->assertTrue($admin->can('delete', $otherAdmin));
        $this->assertTrue($admin->can('delete', $manager));
        $this->assertTrue($admin->can('delete', $student));
        $this->assertFalse($admin->can('delete', $admin));

        $this->assertFalse($student->can('delete', $otherManager));
    }

    public function test_manager_can_delete_reference_data_and_gets_a_clear_message_when_it_is_in_use(): void
    {
        $this->actingAs(User::factory()->manager()->create());

        $unused = DocumentType::create(['name' => 'Unused type']);
        $used = DocumentType::create(['name' => 'Passport']);
        Document::create([
            'student_id' => $this->makeStudent()->id,
            'document_type_id' => $used->id,
            'name' => 'Passport',
            'document_url' => 'documents/x.pdf',
        ]);

        Livewire::test(ListDocumentTypes::class)
            ->callTableAction(DeleteAction::class, $unused)
            ->assertNotified('Deleted');

        Livewire::test(ListDocumentTypes::class)
            ->callTableAction(DeleteAction::class, $used)
            ->assertNotified('This record cannot be deleted');

        $this->assertModelMissing($unused);
        $this->assertModelExists($used);
    }
}

<?php

namespace App\Filament\Resources\StudentResource\Pages\Concerns;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions;

/**
 * Shared by the View and Edit pages: loads the account fields into the form
 * and provides the approve / unapprove / delete header actions.
 */
trait HandlesStudentAccount
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user'] = $this->getRecord()->user?->only(['name', 'email', 'is_approved']) ?? [];

        return $data;
    }

    protected function getApprovalActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Student $record) => $record->user && ! $record->user->is_approved)
                ->requiresConfirmation()
                ->modalDescription('The student will be able to sign in and will receive a confirmation email.')
                ->action(function (Student $record) {
                    StudentResource::approve($record);
                    $this->data['user']['is_approved'] = $record->user->is_approved;
                }),
            Actions\Action::make('unapprove')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->visible(fn (Student $record) => $record->user?->is_approved)
                ->requiresConfirmation()
                ->modalDescription('The student will no longer be able to sign in until approved again.')
                ->action(function (Student $record) {
                    StudentResource::unapprove($record);
                    $this->data['user']['is_approved'] = $record->user->is_approved;
                }),
        ];
    }

    protected function getStudentDeleteAction(): Actions\DeleteAction
    {
        return StudentResource::configureDeleteAction(Actions\DeleteAction::make());
    }
}

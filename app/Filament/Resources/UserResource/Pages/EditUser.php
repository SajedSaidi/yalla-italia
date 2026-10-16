<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Approval metadata and the approval email are handled by the User model.
        if (! $this->record->wasChanged('is_approved')) {
            return;
        }

        $this->record->is_approved
            ? Notification::make()
                ->title('User Approved')
                ->success()
                ->body("{$this->record->name} has been approved and notified by email.")
                ->send()
            : Notification::make()
                ->title('User Unapproved')
                ->warning()
                ->body("{$this->record->name} has been unapproved.")
                ->send();
    }
}

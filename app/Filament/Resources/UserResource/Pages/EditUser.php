<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\UserApproved;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        $record = $this->record;
        $originalData = $this->record->getOriginal();

        // Check if approval status changed from false to true
        if (!$originalData['is_approved'] && $record->is_approved) {
            // Update approval metadata manually since toggle doesn't trigger model observer
            $record->update([
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            // Send approval email
            Mail::to($record->email)->queue(new UserApproved($record));

            \Filament\Notifications\Notification::make()
                ->title('User Approved')
                ->success()
                ->body("User {$record->name} has been approved and notified via email.")
                ->send();
        }

        // Check if approval status changed from true to false
        if ($originalData['is_approved'] && !$record->is_approved) {
            \Filament\Notifications\Notification::make()
                ->title('User Unapproved')
                ->warning()
                ->body("User {$record->name} has been unapproved.")
                ->send();
        }
    }
}

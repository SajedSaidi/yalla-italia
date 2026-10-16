<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\Concerns\HandlesStudentAccount;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditStudent extends EditRecord
{
    use HandlesStudentAccount;

    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getApprovalActions(),
            Actions\ViewAction::make(),
            $this->getStudentDeleteAction(),
        ];
    }

    /**
     * Saves the login account and the student profile together.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->user->update(Arr::only($data['user'] ?? [], ['name', 'email', 'password', 'is_approved']));
            $record->update(Arr::except($data, ['user']));

            return $record;
        });
    }
}

<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\Concerns\HandlesStudentAccount;
use App\Filament\Resources\StudentResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewStudent extends ViewRecord
{
    use HandlesStudentAccount;

    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getApprovalActions(),
            Actions\EditAction::make(),
            $this->getStudentDeleteAction(),
        ];
    }

    protected function getAllRelationManagers(): array
    {
        if (Auth::user()->isManagerOrAdmin())
            return [
                DocumentsRelationManager::class,
                ApplicationsRelationManager::class,
            ];

        return [];
    }
}

<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\UserRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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

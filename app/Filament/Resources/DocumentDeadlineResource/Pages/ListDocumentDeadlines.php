<?php

namespace App\Filament\Resources\DocumentDeadlineResource\Pages;

use App\Filament\Resources\DocumentDeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentDeadlines extends ListRecords
{
    protected static string $resource = DocumentDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

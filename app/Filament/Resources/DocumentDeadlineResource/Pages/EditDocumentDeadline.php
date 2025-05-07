<?php

namespace App\Filament\Resources\DocumentDeadlineResource\Pages;

use App\Filament\Resources\DocumentDeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentDeadline extends EditRecord
{
    protected static string $resource = DocumentDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

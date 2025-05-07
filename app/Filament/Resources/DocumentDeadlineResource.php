<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentDeadlineResource\Pages;
use App\Filament\Resources\DocumentDeadlineResource\RelationManagers;
use App\Models\AcademicYear;
use App\Models\DocumentDeadline;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentDeadlineResource extends Resource
{
    protected static ?string $model = DocumentDeadline::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';  // Icon choice
    protected static ?string $navigationLabel = 'Document Deadlines';
    protected static ?string $navigationGroup = 'System';

    public static function getEloquentQuery(): Builder
    {
        // Eager-load relations for efficient table rendering :contentReference[oaicite:2]{index=2}
        return parent::getEloquentQuery()->with(['academicYear', 'documentType']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Deadline Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('academic_year_id')
                                    ->label('Academic Year')
                                    ->options(AcademicYear::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),

                                Select::make('document_type_id')
                                    ->label('Document Type')
                                    ->options(DocumentType::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),

                                DatePicker::make('deadline')
                                    ->label('Deadline')
                                    ->required(),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3)
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('documentType.name')
                    ->label('Document Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No nested relation managers needed here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListDocumentDeadlines::route('/'),
            'create'  => Pages\CreateDocumentDeadline::route('/create'),
            // 'view'    => Pages\ViewDocumentDeadline::route('/{record}'),
            'edit'    => Pages\EditDocumentDeadline::route('/{record}/edit'),
        ];
    }
}

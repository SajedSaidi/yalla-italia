<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Documents';
    protected static ?string $navigationGroup = 'Academics';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('student_id')
                                            ->label('Student')
                                            ->relationship('student', 'user.name')
                                            ->options(
                                                function () {
                                                    return  Student::all()
                                                        ->pluck('user.name', 'id')
                                                        ->toArray();
                                                }
                                            )
                                            ->searchable()
                                            ->required(),

                                        Select::make('document_type_id')
                                            ->label('Type')
                                            ->relationship('documentType', 'name')
                                            ->options(
                                                function () {
                                                    return  DocumentType::all()
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }
                                            )
                                            ->searchable()
                                            ->required(),

                                        TextInput::make('name')
                                            ->label('Document Name')
                                            ->required()
                                            ->maxLength(255),

                                        FileUpload::make('document_url')
                                            ->label('Upload File')
                                            ->directory('documents')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->required()
                                            ->openable()
                                            ->downloadable()
                                            ->previewable(),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'submitted' => 'Submitted',
                                                'accepted'  => 'Accepted',
                                                'rejected'  => 'Rejected',
                                                'draft'     => 'Draft',
                                                'missing'   => 'Missing',
                                            ])
                                            ->default('submitted')
                                            ->required(),

                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->rows(3),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('student.user.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('documentType.name')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('status')->sortable()->badge(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}

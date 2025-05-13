<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Student;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('student_id')
                    ->default(fn($livewire) => $livewire->ownerRecord->id),

                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
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
                                            ->required()
                                            ->preload()
                                            ->rules([
                                                // Filament's "Get" helper lets us read other fields' values:
                                                fn(Get $get): Closure => function (
                                                    string  $attribute,
                                                    $value,
                                                    Closure $fail,
                                                ) use ($get) {
                                                    $student_id = $get('student_id');
                                                    $document_type_id = $get('document_type_id');
                                                    // Don’t validate until all three IDs are chosen
                                                    if (! $student_id || ! $document_type_id) {
                                                        return;
                                                    }

                                                    $query = Document::query()
                                                        ->where('student_id', $student_id)
                                                        ->where('document_type_id', $document_type_id);

                                                    // On edit, ignore the current record:
                                                    if ($get('id')) {
                                                        $query->where('id', '!=', $get('id'));
                                                    }

                                                    if ($query->exists()) {
                                                        $fail('Student already has this document!');
                                                    }
                                                },
                                            ]),

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

                                        RichEditor::make('notes')
                                            ->label('Notes (Optional)')
                                            ->disableToolbarButtons(['attachFiles'])
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'h2',
                                                'h3',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                                'undo',
                                                'redo',
                                            ])
                                            ->disableGrammarly()
                                            ->columnSpanFull()
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document')
            ->columns([
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}

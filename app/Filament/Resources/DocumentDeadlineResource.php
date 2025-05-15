<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentDeadlineResource\Pages;
use App\Filament\Resources\DocumentDeadlineResource\RelationManagers;
use App\Models\AcademicYear;
use App\Models\DocumentDeadline;
use App\Models\DocumentType;
use App\Models\University;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentDeadlineResource extends Resource
{
    protected static ?string $model = DocumentDeadline::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';  // Icon choice
    protected static ?string $navigationLabel = 'Document Deadlines';
    protected static ?string $navigationGroup = 'System';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('academic_year_id')
                                    ->label('Academic Year')
                                    ->options(AcademicYear::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                Select::make('document_type_id')
                                    ->label('Document Type')
                                    ->options(DocumentType::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                Select::make('university_id')
                                    ->label('University')
                                    ->options(University::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->rules([
                                        // Filament's "Get" helper lets us read other fields' values:
                                        fn(Get $get): Closure => function (
                                            string  $attribute,
                                            $value,
                                            Closure $fail,
                                        ) use ($get) {
                                            $academic_year_id = $get('academic_year_id');
                                            $document_type_id = $get('document_type_id');
                                            $university_id = $get('university_id');
                                            $education_level = $get('education_level');
                                            // Don’t validate until all three IDs are chosen
                                            if (! $academic_year_id || ! $document_type_id || ! $university_id || ! $education_level) {
                                                return;
                                            }

                                            $query = DocumentDeadline::query()
                                                ->where('academic_year_id', $academic_year_id)
                                                ->where('document_type_id', $document_type_id)
                                                ->where('university_id', $university_id)
                                                ->where('education_level', $education_level);

                                            // On edit, ignore the current record:
                                            if ($get('id')) {
                                                $query->where('id', '!=', $get('id'));
                                            }

                                            if ($query->exists()) {
                                                $fail('A Document Deadline with the same Academic Year, Document Type, University, Education Level already exists!');
                                            }
                                        },
                                    ]),

                                Select::make('education_level')
                                    ->label('Education Level')
                                    ->options([
                                        'single_cycle' => 'Single Cycle',
                                        'bachelor' => 'Bachelor',
                                        'master' => 'Master',
                                        'phd' => 'PhD',
                                    ])
                                    ->required(),

                                DatePicker::make('deadline')
                                    ->label('Deadline')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('university.name')
                    ->label('University')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('documentType.name')
                    ->label('Document Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('education_level')
                    ->badge()
                    ->label('Education Level')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->sortable(),
            ])
            ->filters([

                // filter by university
                Tables\Filters\SelectFilter::make('university_id')
                    ->label('University')
                    ->relationship('university', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->placeholder('All Universities'),

                // filter by academic year
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->placeholder('All Academic Years'),

                // filter by document type
                Tables\Filters\SelectFilter::make('document_type_id')
                    ->label('Document Type')
                    ->relationship('documentType', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->placeholder('All Document Types'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),

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

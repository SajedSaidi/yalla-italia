<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\AcademicYear;
use App\Models\Major;
use App\Models\Program;
use App\Models\University;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Programs';
    protected static ?string $navigationGroup = 'Academics';

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
                                Select::make('university_id')
                                    ->label('University')
                                    ->options(University::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                Select::make('major_id')
                                    ->relationship('major', 'composite_title')
                                    ->label('Major')
                                    ->options(
                                        fn() => Major::all()
                                            ->pluck('composite_title', 'id')
                                            ->toArray()
                                    )
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                Select::make('academic_year_id')
                                    ->label('Academic Year')
                                    ->options(AcademicYear::pluck('name', 'id'))
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
                                            $uniId   = $get('university_id');
                                            $majorId = $get('major_id');
                                            $yearId  = $value;

                                            // Don’t validate until all three IDs are chosen
                                            if (! $uniId || ! $majorId || ! $yearId) {
                                                return;
                                            }

                                            $query = Program::query()
                                                ->where('university_id',     $uniId)
                                                ->where('major_id',           $majorId)
                                                ->where('academic_year_id',   $yearId);

                                            // On edit, ignore the current record:
                                            if ($get('id')) {
                                                $query->where('id', '!=', $get('id'));
                                            }

                                            if ($query->exists()) {
                                                $fail('A Program with this University, Major, and Academic Year already exists.');
                                            }
                                        },
                                    ]),

                                DatePicker::make('application_deadline')
                                    ->label('Application Deadline')
                                    ->required(),

                                TextInput::make('application_fee')
                                    ->label('Application Fee')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0)
                                    ->required(),

                                TextInput::make('enrollment_fee')
                                    ->label('Enrollment Fee')
                                    ->numeric()
                                    ->prefix('€')
                                    ->nullable(),
                            ]),
                        RichEditor::make('description')
                            ->label('Description (Optional)')
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
                    ])
                    ->columns(1),
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

                TextColumn::make('major.composite_title')
                    ->label('Major')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('application_deadline')
                    ->label('Application Deadline')
                    ->sortable(),

                TextColumn::make('application_fee')
                    ->label('Application Fee')
                    ->money('euro')
                    ->sortable(),

                TextColumn::make('enrollment_fee')
                    ->label('Enrollment Fee')
                    ->money('euro')
                    ->sortable(),
            ])
            ->filters([
                // Add any necessary filters here
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
            // Define any relation managers here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
            // 'view' => Pages\ViewProgram::route('/{record}'),
        ];
    }
}

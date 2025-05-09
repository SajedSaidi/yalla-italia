<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers;
use App\Models\Application;
use App\Models\Program;
use App\Models\Student;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
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


class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Applications';
    protected static ?string $navigationGroup = 'Academics';

    protected static bool $shouldRegisterNavigation = false;

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
                                            ->preload()
                                            ->searchable()
                                            ->required(),

                                        Select::make('program_id')
                                            ->label('Program')
                                            ->relationship('program', 'composite_title')
                                            ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
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
                                                    $student_id = $get('student_id');
                                                    $program_id = $get('program_id');
                                                    // Don’t validate until all three IDs are chosen
                                                    if (! $student_id || ! $program_id) {
                                                        return;
                                                    }

                                                    $query = Application::query()
                                                        ->where('student_id', $student_id)
                                                        ->where('program_id', $program_id);

                                                    // On edit, ignore the current record:
                                                    if ($get('id')) {
                                                        $query->where('id', '!=', $get('id'));
                                                    }

                                                    if ($query->exists()) {
                                                        $fail('Student already applied to this program!');
                                                    }
                                                },
                                            ]),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'accepted' => 'Accepted',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->default('pending')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('student.user.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('program.composite_title')
                    ->label('Program')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('program_id')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
                    ->label('Program'),
            ])
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}

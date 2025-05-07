<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\AcademicYear;
use App\Models\Major;
use App\Models\Program;
use App\Models\University;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Programs';
    protected static ?string $navigationGroup = 'Academics';

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
                                    ->searchable()
                                    ->required(),

                                Select::make('major_id')
                                    ->label('Major')
                                    ->options(Major::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),

                                Select::make('academic_year_id')
                                    ->label('Academic Year')
                                    ->options(AcademicYear::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),

                                DatePicker::make('application_deadline')
                                    ->label('Application Deadline')
                                    ->required(),

                                TextInput::make('application_fee')
                                    ->label('Application Fee')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->required(),

                                TextInput::make('enrollment_fee')
                                    ->label('Enrollment Fee')
                                    ->numeric()
                                    ->prefix('$')
                                    ->nullable(),
                            ]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->nullable(),
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

                TextColumn::make('major.name')
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
                    ->money('usd')
                    ->sortable(),

                TextColumn::make('enrollment_fee')
                    ->label('Enrollment Fee')
                    ->money('usd')
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
                Tables\Actions\DeleteBulkAction::make(),
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

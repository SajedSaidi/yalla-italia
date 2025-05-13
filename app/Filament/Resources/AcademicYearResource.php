<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Academic Years';
    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Academic Year Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('start_year')
                                    ->label('Start Year')
                                    ->integer()
                                    ->required()
                                    ->minValue(2000)
                                    ->maxValue(2100)
                                    ->lt('end_year'),
                                TextInput::make('end_year')
                                    ->label('End Year')
                                    ->integer()
                                    ->required()
                                    ->minValue(2000)
                                    ->maxValue(2100)
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('start_year')->sortable(),
                TextColumn::make('end_year')->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
}

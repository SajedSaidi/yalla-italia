<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers;
use App\Models\Application;
use App\Models\Program;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
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

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Applications';
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
                                            ->preload()
                                            ->searchable()
                                            ->required(),

                                        Select::make('program_id')
                                            ->label('Program')
                                            ->relationship('program', 'composite_title')
                                            ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
                                            ->preload()
                                            ->searchable()
                                            ->required(),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'accepted' => 'Accepted',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->default('pending')
                                            ->required(),

                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->rows(4)
                                            ->nullable(),
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
                    ->label('Applied At')
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
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
            //
        ];
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

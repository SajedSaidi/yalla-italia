<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Filament\Resources\StudentResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\UserRelationManager;
use App\Models\Nationality;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'Academics';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canEdit($record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Details')
                    ->schema([
                        Section::make('User Information')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('User')
                                            ->relationship('user', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->required()
                                            ->options(function () {
                                                return User::query()
                                                    ->whereDoesntHave('student')
                                                    ->where('role', 'student')
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            }),
                                        Forms\Components\TextInput::make('user_email_display')
                                            ->label('Email')
                                            ->formatStateUsing(fn($record) => $record?->user?->email ?? '-')
                                            ->disabled()
                                            ->visibleOn(['view', 'edit'])
                                            ->dehydrated(false),
                                        TextInput::make('phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(20),
                                        DatePicker::make('date_of_birth')
                                            ->label('Date of Birth')
                                            ->required()
                                            ->maxDate(now())
                                            ->displayFormat('d/m/Y')
                                            ->format('Y-m-d'),
                                        TextInput::make('place_of_birth')
                                            ->label('Place of Birth')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('address')
                                            ->label('Address')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('nationality_id')
                                            ->label('Nationality')
                                            ->preload()
                                            ->searchable()
                                            ->required()
                                            ->options(function () {
                                                return Nationality::all()
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            }),
                                        Select::make('qualifications')
                                            ->label('Qualifications')
                                            ->multiple()
                                            ->relationship('qualifications', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->required()
                                            ->columnSpanFull(),
                                        Select::make('languageCertificates')
                                            ->label('Language Certificates')
                                            ->multiple()
                                            ->relationship('languageCertificates', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->columnSpanFull()
                                    ]),
                            ])->collapsible(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('date_of_birth')
                    ->date('d/m/Y'),
                TextColumn::make('qualifications.name')
                    ->badge()
                    ->color('primary')
                    ->label('Qualifications'),
                TextColumn::make('languageCertificates.name')
                    ->badge()
                    ->color('success')
                    ->label('Language Certificates'),
                TextColumn::make('nationality.name')
                    ->searchable()
                    ->label('Nationality'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nationality_id')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn() => Nationality::all()->pluck('name', 'id')->toArray())
                    ->label('Nationality'),

                Tables\Filters\SelectFilter::make('qualifications')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('qualifications', 'name')
                    ->label('Qualifications'),

                Tables\Filters\SelectFilter::make('languageCertificates')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('languageCertificates', 'name')
                    ->label('Language Certificates'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->isStudent()) {
                    return $query->where('id', Auth::user()->student->id);
                }
            })
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
            DocumentsRelationManager::class,
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListStudents::route('/'),
            'create'  => Pages\CreateStudent::route('/create'),
            'edit'    => Pages\EditStudent::route('/{record}/edit'),
            'view'   => Pages\ViewStudent::route('/{record}'),
        ];
    }
}

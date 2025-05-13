<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Filament\Resources\StudentResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\UserRelationManager;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'Academics';

    public static function canCreate(): bool
    {
        return auth()->user()->isManagerOrAdmin();
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->isManagerOrAdmin();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Details')
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
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),
                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->maxDate(now()),
                                TextInput::make('address')
                                    ->label('Address')
                                    ->maxLength(255),
                                TextInput::make('nationality')
                                    ->label('Nationality')
                                    ->maxLength(100),
                            ]),
                        RichEditor::make('qualifications')
                            ->required()
                            ->label('Qualifications')
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
                    ])->collapsible(),
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
                TextColumn::make('phone'),
                TextColumn::make('date_of_birth')
                    ->label('DOB')
                    ->date(),
                TextColumn::make('nationality'),
            ])
            ->filters([])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->isStudent()) {
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

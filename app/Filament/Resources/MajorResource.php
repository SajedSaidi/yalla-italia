<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MajorResource\Pages;
use App\Filament\Resources\MajorResource\RelationManagers;
use App\Models\Major;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Markdown;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MajorResource extends Resource
{
    protected static ?string $model = Major::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Majors';
    protected static ?string $navigationGroup = 'System';

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
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Major Name')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Level')
                            ->options([
                                'single_cycle' => 'Single Cycle',
                                'bachelor' => 'Bachelor',
                                'master' => 'Master',
                                'phd' => 'PhD',
                            ])
                            ->required()
                            ->rules([
                                // Filament's "Get" helper lets us read other fields' values:
                                fn(Get $get): Closure => function (
                                    string  $attribute,
                                    $value,
                                    Closure $fail,
                                ) use ($get) {
                                    $name   = $get('name');
                                    $type = $get('type');

                                    // Don’t validate until all three IDs are chosen
                                    if (! $name || ! $type) {
                                        return;
                                    }

                                    $query = Major::query()
                                        ->where('name', $name)
                                        ->where('type', $type);

                                    // On edit, ignore the current record:
                                    if ($get('id')) {
                                        $query->where('id', '!=', $get('id'));
                                    }

                                    if ($query->exists()) {
                                        $fail('A Major with the same Name and Level already exists!');
                                    }
                                },
                            ]),

                        Select::make('languages')
                            ->label('Languages')
                            ->multiple()                            // enable multi-select
                            ->relationship('languages', 'name')     // BelongsToMany relation
                            ->preload()                             // load all options on mount
                            ->searchable(),
                    ]),
                Forms\Components\Section::make()
                    ->schema([

                        RichEditor::make('description')
                            // ->label('Description (Optional)')
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'single_cycle' => 'warning',  // Orange - Special type
                        'bachelor' => 'info',         // Blue - Undergraduate
                        'master' => 'success',        // Green - Graduate
                        'phd' => 'danger',           // Red - Highest level
                        default => 'gray',
                    }),
                TextColumn::make('languages.name')
                    ->badge()
                    ->color('primary')  // Consistent color for all languages
                    ->label('Languages')
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'single_cycle' => 'Single Cycle',
                        'bachelor' => 'Bachelor',
                        'master' => 'Master',
                        'phd' => 'PhD',
                    ])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMajors::route('/'),
            'create' => Pages\CreateMajor::route('/create'),
            'edit' => Pages\EditMajor::route('/{record}/edit'),
        ];
    }
}

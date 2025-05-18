<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversityResource\Pages;
use App\Filament\Resources\UniversityResource\RelationManagers;
use App\Models\University;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UniversityResource extends Resource
{
    protected static ?string $model = University::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Universities';
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
                Group::make()
                    ->schema([
                        Forms\Components\Section::make()->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('address')
                                ->required()
                                ->maxLength(255),
                        ]),
                    ]),
                Group::make()
                    ->schema([
                        Forms\Components\Section::make()->schema([

                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(20),

                            TextInput::make('website_url')
                                ->url()
                                // ->prefix('https://')
                                ->prefixIcon('heroicon-m-globe-alt')
                                ->required()
                                ->maxLength(255),

                            Textarea::make('description')
                                ->rows(3)
                                ->maxLength(65535),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('website_url')
                    ->label('Website')
                    ->url(fn($record) => $record->website_url)
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListUniversities::route('/'),
            'create' => Pages\CreateUniversity::route('/create'),
            'edit' => Pages\EditUniversity::route('/{record}/edit'),
        ];
    }
}

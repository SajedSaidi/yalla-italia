<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'user';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('Enter user name')
                    ->columnSpan(1),
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->required()
                    ->placeholder('Enter email address')
                    ->columnSpan(1),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->visibleOn('create')
                    ->required()
                    ->minLength(8)
                    ->maxLength(191)
                    ->placeholder('Enter password')
                    ->helperText('Password must be at least 8 characters')
                    ->columnSpan(1),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'student' => 'Student',
                    ])
                    ->required()
                    ->default('user')
                    ->label('User Role')
                    ->columnSpan(1)

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->label('Role'),
            ])
            ->filters([])
            ->actions([
                // Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

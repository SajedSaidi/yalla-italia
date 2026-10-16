<?php

namespace App\Filament\Resources;

use App\Filament\Filters\RegisteredBetweenFilter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin only (see UserPolicy). Managers handle student accounts from StudentResource.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
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
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->maxLength(191)
                            ->placeholder(fn (string $operation) => $operation === 'create' ? 'Enter password' : 'Leave blank to keep the current password')
                            ->helperText('Password must be at least 8 characters')
                            ->hiddenOn('view')
                            ->columnSpan(1),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'student' => 'Student',
                            ])
                            ->required()
                            ->default('student')
                            ->label('User Role')
                            ->helperText('Student accounts with a full profile are best created from the Students page.')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Approval Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('User Approved')
                            ->helperText('Approved students can sign in. They are emailed automatically when approved.'),

                        Forms\Components\Placeholder::make('approved_at_display')
                            ->label('Approved At')
                            ->content(fn (?User $record) => $record?->approved_at?->format('M d, Y \a\t H:i') ?? '—')
                            ->visible(fn (?User $record) => (bool) $record?->is_approved),

                        Forms\Components\Placeholder::make('approved_by_display')
                            ->label('Approved By')
                            ->content(fn (?User $record) => $record?->approvedBy?->name ?? '—')
                            ->visible(fn (?User $record) => (bool) $record?->is_approved),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'success',
                        'manager' => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'student' => 'Student',
                    ]),
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All users')
                    ->trueLabel('Approved users')
                    ->falseLabel('Pending approval'),
                RegisteredBetweenFilter::make(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record) => ! $record->is_approved)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->approve(Auth::user());

                        Notification::make()
                            ->title('User Approved')
                            ->success()
                            ->body("{$record->name} has been approved and notified by email.")
                            ->send();
                    }),
                Action::make('unapprove')
                    ->label('Unapprove')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->is_approved)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->unapprove();

                        Notification::make()
                            ->title('User Unapproved')
                            ->warning()
                            ->body("{$record->name} has been unapproved.")
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $pending = $records->reject(fn (User $record) => $record->is_approved);
                            $pending->each(fn (User $record) => $record->approve(Auth::user()));

                            Notification::make()
                                ->title('Users Approved')
                                ->success()
                                ->body("{$pending->count()} user(s) have been approved and notified.")
                                ->send();
                        }),
                    static::deleteAllowedBulkAction(),
                ]),
            ]);
    }

    /**
     * Bulk delete that only removes the selected users the current user may delete
     * (never themselves, and never admins/managers when run by a manager).
     */
    public static function deleteAllowedBulkAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->using(function (Collection $records) {
                $allowed = $records->filter(fn (User $record) => Auth::user()->can('delete', $record));

                DB::transaction(fn () => $allowed->each->delete());

                $skipped = $records->count() - $allowed->count();

                if ($skipped > 0) {
                    Notification::make()
                        ->title("{$skipped} selected account(s) were skipped")
                        ->body('You are not allowed to delete them.')
                        ->warning()
                        ->send();
                }
            });
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
